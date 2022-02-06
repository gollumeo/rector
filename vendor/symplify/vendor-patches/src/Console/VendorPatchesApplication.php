<?php

declare (strict_types=1);
namespace RectorPrefix20220206\Symplify\VendorPatches\Console;

use RectorPrefix20220206\Symfony\Component\Console\Application;
use RectorPrefix20220206\Symfony\Component\Console\Command\Command;
final class VendorPatchesApplication extends \RectorPrefix20220206\Symfony\Component\Console\Application
{
    /**
     * @param Command[] $commands
     */
    public function __construct(array $commands)
    {
        $this->addCommands($commands);
        parent::__construct();
    }
}
