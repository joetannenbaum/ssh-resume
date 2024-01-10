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
	"syscall"
	"time"
	"unsafe"

	"github.com/charmbracelet/log"
	"github.com/charmbracelet/ssh"
	"github.com/charmbracelet/wish"
	"github.com/charmbracelet/wish/comment"
	"github.com/charmbracelet/wish/logging"
	"github.com/creack/pty"
)

func setWinsize(f *os.File, w, h int) {
	syscall.Syscall(syscall.SYS_IOCTL, f.Fd(), uintptr(syscall.TIOCSWINSZ),
		uintptr(unsafe.Pointer(&struct{ h, w, x, y uint16 }{uint16(h), uint16(w), 0, 0})))
}

func main() {

	var host = flag.String("host", "127.0.0.1", "Host address for SSH server to listen")
	var port = flag.Int("port", 23234, "Port for SSH server to listen")

	flag.Parse()

	s, err := wish.NewServer(
		wish.WithAddress(fmt.Sprintf("%s:%d", *host, *port)),
		wish.WithHostKeyPath(".ssh/term_info_ed25519"),
		wish.WithMiddleware(
			comment.Middleware("Thanks for checking out my resume. Have a great day!"),
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
