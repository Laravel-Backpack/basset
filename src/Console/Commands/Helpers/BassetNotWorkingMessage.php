<?php

namespace Backpack\Basset\Console\Commands\Helpers;

trait BassetNotWorkingMessage
{
    public function bassetNotWorkingMessage($message)
    {
        $this->components->twoColumnDetail($message, '<fg=red;options=bold>ERROR</>');
        $this->newLine();
        $this->line('  <fg=gray>│ Backpack Basset failed to check it\'s working properly.</>');
        $this->line('  <fg=gray>│</>');
        $this->line('  <fg=gray>│ This may be due to multiple issues. Please ensure:</>');
        $this->line('  <fg=gray>│  1) APP_URL is correctly set in the <fg=white>.env</> file.</>');
        $this->line('  <fg=gray>│  2) Your server is running and accessible at <fg=white>'.url('').'</>.</>');
        $this->line('  <fg=gray>│  3) The <fg=white>'.config('backpack.basset.disk').'</> disk is properly configured in <fg=white>config/filesystems.php</>.</>');
        $this->line('  <fg=gray>│     Optionally, basset provides a disk named "basset", you can use it instead.</>');
        $this->line('  <fg=gray>│  4) The storage symlink exists and is valid (by default: public/storage).</>');
        $this->line('  <fg=gray>│</>');
        $this->line('  <fg=gray>│ For more information and solutions, please visit the Backpack Basset FAQ at:</>');
        $this->line('  <fg=gray>│ <fg=white>https://github.com/laravel-backpack/basset#faq</></>');
        $this->newLine();
    }
}