<?php

namespace Igniter\Queues\Commands;

use CodeIgniter\CLI\CLI;
use App\Shared\SharedDefaults;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;

class MakeQueueJob extends BaseCommand
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
    protected $name = 'make:job';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Creates a new Job class';

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
    protected $arguments = [
        'name' => 'Name of the Job to be created'
    ];

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
        $name = array_shift($params);

        if (empty($name)) {
            $name = CLI::prompt('Job name', null, 'required'); // @codeCoverageIgnore
        }
        $this->createDirectories();
        file_put_contents(
            ROOTPATH .'app/Jobs/'. trim($name . '.php'),
            $this->compileJobTemp(trim($name))
        );

        CLI::write("Job \"{$name}\" successfully created.", 'green');
    }

    protected function compileJobTemp($jobName)
    {
        $job = str_replace('{{namespace}}', APP_NAMESPACE, file_get_contents(__DIR__.'/../Queue/temps/job.temp'));
        return str_replace(
            '{{className}}',
            $jobName,
            $job
        );
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir($directory = ROOTPATH . 'app/Jobs')) {
            mkdir($directory, 0755, true);
        }
    }
}
