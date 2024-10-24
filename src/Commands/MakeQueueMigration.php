<?php

namespace Igniter\Queues\Commands;

use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;

class MakeQueueMigration extends BaseCommand
{
    use GeneratorTrait;
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Queue';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'queue:table';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Creates the queue_table migration script';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:job [arguments] [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        
    ];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {   
        file_put_contents(
            ROOTPATH .'app/Database/Migrations/' . date('Ymdhis_') . 'queue_jobs.php',
            $this->compileMigrationTemp()
        );

        CLI::write("Migration successfully created.", 'green');
    }

    protected function compileMigrationTemp()
    {
        return file_get_contents(__DIR__.'/Queue/temps/jobs_table.temp');
    }

    
}
