<?php

namespace ChewieLab;

class CommanderPanel
{
    public bool $active = false;

    public string $currentDir = '';

    public array $files = [];

    public int $page = 1;

    public int $selectedIndex = 0;

    public function __construct(string $startingDir = null)
    {
        $this->setDirectory($startingDir ?? getcwd());
    }

    public function setDirectory(string $name)
    {
        $this->currentDir = realpath($this->currentDir . '/' . $name);
        $this->setFiles();
        $this->selectedIndex = count($this->files) > 1 ? 1 : 0;
    }

    public function setFiles(): void
    {
        $this->files = collect(scandir($this->currentDir))
            ->reject(fn ($file) => in_array($file, ['.', '..']))
            ->map(fn ($file) => [
                'name'          => $file,
                'type'          => is_dir($this->currentDir . '/' . $file) ? 'dir' : 'file',
                'extension'     => pathinfo($file, PATHINFO_EXTENSION),
                'filename'      => pathinfo($file, PATHINFO_FILENAME),
                'last_modified' => date('Y-m-d H:ia', filemtime($this->currentDir . '/' . $file)),
            ])
            ->sortBy(fn ($file) => $file['type'] === 'dir' ? 0 : 1)
            ->values()
            ->prepend([
                'name'      => '..',
                'type'      => 'dir',
                'extension' => '',
                'filename'  => '..',
            ])
            ->all();
    }

    public function visible(int $perChunk)
    {
        return collect($this->files)
            ->chunk($perChunk)
            ->slice($this->page - 1, $this->page + 2);
    }
}
