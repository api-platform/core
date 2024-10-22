<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Laravel\Console\Maker\Utils;

trait SuccessMessageTrait
{
    private function writeSuccessMessage(string $filePath, StateTypeEnum $stateTypeEnum): void
    {
        $stateText = strtolower($stateTypeEnum->name);

        $this->newLine();
        $this->line(' <bg=green;fg=white>          </>');
        $this->line(' <bg=green;fg=white> Success! </>');
        $this->line(' <bg=green;fg=white>          </>');
        $this->newLine();
        $this->line('<fg=blue>created</>: <fg=white;options=underscore>'.$filePath.'</>');
        $this->newLine();
        $this->line("Next: Open your new state $stateText class and start customizing it.");
    }
}
