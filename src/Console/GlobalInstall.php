<?php

namespace Auxfin\Global\Console;

use Illuminate\Console\Command;
use TomatoPHP\ConsoleHelpers\Traits\RunCommand;

class GlobalInstall extends Command
{
    use RunCommand;


    protected $name = 'global:install';


    protected $description = 'install package and publish assets';

    public function __construct()
    {
        parent::__construct();
    }



    public function handle()
    {
        $this->info('Publish Vendor Assets');
        $this->artisanCommand(["migrate"]);
        $this->artisanCommand(["optimize:clear"]);
        $this->info('Global installed successfully.');
    }
}
