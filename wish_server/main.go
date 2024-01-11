package main

// An example Bubble Tea server. This will put an ssh session into alt screen
// and continually print up to date terminal information.

import (
	"context"
	"errors"
	"flag"
	"fmt"
	"io"
	"os"
	"os/exec"
	"os/signal"
	"strings"
	"syscall"
	"time"
	"unsafe"

	"github.com/charmbracelet/log"
	"github.com/charmbracelet/ssh"
	"github.com/charmbracelet/wish"
	"github.com/charmbracelet/wish/logging"
	"github.com/creack/pty"
	"github.com/muesli/termenv"
)

func setWinsize(f *os.File, w, h int) {
	syscall.Syscall(syscall.SYS_IOCTL, f.Fd(), uintptr(syscall.TIOCSWINSZ),
		uintptr(unsafe.Pointer(&struct{ h, w, x, y uint16 }{uint16(h), uint16(w), 0, 0})))
}

type sshOutput struct {
	ssh.Session
	tty *os.File
}

func (s *sshOutput) Write(p []byte) (int, error) {
	return s.Session.Write(p)
}

func (s *sshOutput) Read(p []byte) (int, error) {
	return s.Session.Read(p)
}

func (s *sshOutput) Name() string {
	return s.tty.Name()
}

func (s *sshOutput) Fd() uintptr {
	return s.tty.Fd()
}

type sshEnviron struct {
	environ []string
}

func (s *sshEnviron) Getenv(key string) string {
	for _, v := range s.environ {
		if strings.HasPrefix(v, key+"=") {
			return v[len(key)+1:]
		}
	}
	return ""
}

func (s *sshEnviron) Environ() []string {
	return s.environ
}

func outputFromSession(s ssh.Session) *termenv.Output {
	sshPty, _, _ := s.Pty()
	_, tty, err := pty.Open()
	if err != nil {
		panic(err)
	}
	o := &sshOutput{
		Session: s,
		tty:     tty,
	}
	environ := s.Environ()
	environ = append(environ, fmt.Sprintf("TERM=%s", sshPty.Term))
	e := &sshEnviron{
		environ: environ,
	}
	return termenv.NewOutput(o, termenv.WithUnsafe(), termenv.WithEnvironment(e))
}

func main() {

	var host = flag.String("host", "127.0.0.1", "Host address for SSH server to listen")
	var port = flag.Int("port", 23234, "Port for SSH server to listen")

	flag.Parse()

	s, err := wish.NewServer(
		wish.WithAddress(fmt.Sprintf("%s:%d", *host, *port)),
		wish.WithHostKeyPath(".ssh/term_info_ed25519"),
		wish.WithMiddleware(
			func(h ssh.Handler) ssh.Handler {
				return func(s ssh.Session) {
					h(s)

					output := outputFromSession(s)
					p := output.ColorProfile()

					fmt.Fprintf(s, "\n┏┳      ┏┳┓          ┓")
					fmt.Fprintf(s, "\n ┃┏┓┏┓   ┃ ┏┓┏┓┏┓┏┓┏┓┣┓┏┓┓┏┏┳┓")
					fmt.Fprintf(s, "\n┗┛┗┛┗    ┻ ┗┻┛┗┛┗┗ ┛┗┗┛┗┻┗┻┛┗┗")

					fmt.Fprintf(s, "\n\n%s",
						output.String("Thanks for stopping by!").Bold(),
					)

					fmt.Fprintf(s, "\n\nWant to share this? That would be dope (thank you). Here's an easy way to do it:")

					fmt.Fprintf(s, "\n\n%s\n\n",
						output.String("https://twitter.com/intent/tweet?text=%3E%20ssh%20ssh.resume.joe.codes&via=joetannenbaum").Foreground(p.Color("#66C2CD")),
					)
				}
			},
			func(h ssh.Handler) ssh.Handler {
				return func(s ssh.Session) {
					ptyReq, winCh, isPty := s.Pty()

					cmd := exec.Command("php", "lab/resume.php")

					if isPty {
						cmd.Env = append(cmd.Env, fmt.Sprintf("TERM=%s", ptyReq.Term))
						f, err := pty.Start(cmd)
						if err != nil {
							panic(err)
						}

						go func() {
							for win := range winCh {
								setWinsize(f, win.Width, win.Height)
							}
						}()

						go func() {
							io.Copy(f, s) // stdin
						}()
						io.Copy(s, f) // stdout
						cmd.Wait()
					} else {
						io.WriteString(s, "No PTY requested.\n")
						s.Exit(1)
					}

					h(s)
				}
			},
			logging.Middleware(),
		),
	)
	if err != nil {
		log.Error("could not start server", "error", err)
	}

	done := make(chan os.Signal, 1)
	signal.Notify(done, os.Interrupt, syscall.SIGINT, syscall.SIGTERM)
	log.Info("Starting SSH server", "host", host, "port", port)
	go func() {
		if err = s.ListenAndServe(); err != nil && !errors.Is(err, ssh.ErrServerClosed) {
			log.Error("could not start server", "error", err)
			done <- nil
		}
	}()

	<-done
	log.Info("Stopping SSH server")
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer func() { cancel() }()
	if err := s.Shutdown(ctx); err != nil && !errors.Is(err, ssh.ErrServerClosed) {
		log.Error("could not stop server", "error", err)
	}
}
