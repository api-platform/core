<?php

namespace ApiPlatform\Laravel\Console\Maker\Utils;

trait SuccessMessageTrait
{
    private function writeSuccessMessage(string $filePath): void
    {
        $this->newLine();
        $this->line(' <bg=green;fg=white>          </>');
        $this->line(' <bg=green;fg=white> Success! </>');
        $this->line(' <bg=green;fg=white>          </>');
        $this->newLine();
        $this->line('<fg=blue>created</>: <fg=white;options=underscore>' . $filePath . '</>');
        $this->newLine();
        $this->line('Next: Open your new state provider class and start customizing it.');
    }
}
