<?php

namespace AlexBowers\NovaCli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;

class NewCommand extends Command
{
    protected $user;
    protected $config_path;
    protected $config_file;
    protected $config;
    protected $package;

    protected $question;

    protected function configure()
    {
        $this->setName('new')
            ->setDescription('Create a new Nova CLI tool');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem;
        $this->question = $this->getHelper('question');

        $this->user = $_SERVER['USER'];
        $this->config_path = "/Users/{$this->user}/.novacli";
        $this->config_file = $this->config_path . '/config.json';

        $this->config = [
            'author_name' => null,
            'author_username' => null,
            'author_email' => null,
            'vendor' => null,
            'namespace_vendor' => null,
        ];

        if (!$filesystem->exists($this->config_path)) {
            $filesystem->mkdir($this->config_path, 0755);
        }

        if (!$filesystem->exists($this->config_file)) {
            $filesystem->dumpFile($this->config_file, json_encode($this->config, JSON_PRETTY_PRINT));
        }

        $this->config = json_decode(file_get_contents($this->config_file), true);

        foreach ($this->config as $config_option => $config_value) {
            if (is_null($config_value)) {
                $question = null;
                switch ($config_option) {
                    case 'vendor':
                        $question = new Question("What is your vendor name? [alexbowers]", "alexbowers");
                        $question->setNormalizer(function ($value) {
                            $value = str_replace(' ', '', $value);
                            return strtolower($value);
                        });
                        break;
                    case 'author_email':
                        $question = new Question("What is your email address? [example@example.com]", "example@example.com");
                    break;
                    case 'author_name':
                        $question = new Question("What is your name? [Alex Bowers]", "Alex Bowers");
                    break;
                    case 'author_username':
                        $question = new Question("What is your username? [alexbowers]", "Alex Bowers");
                    break;
                    case 'namespace_vendor':
                        $question = new Question("What is your vendor namespace? [alexbowers]", "alexbowers");
                        $question->setNormalizer(function ($value) {
                            return str_replace(' ', '', $value);
                        });
                    break;
                }

                if (!is_null($question)) {
                    $this->config[$config_option] = $this->question->ask($input, $output, $question);
                }
            }
        }

        $filesystem->dumpFile($this->config_file, json_encode($this->config, JSON_PRETTY_PRINT));

        if (count(scandir(getcwd())) !== 2) {
            throw new \Exception("You are not in an empty directory. You probably forgot to create your package directory and change into it.");
        }

        $this->package = [
            'package_name' => null,
            'package_description' => null,
            'namespace_tool_name' => null,
        ];

        foreach ($this->package as $package_option => $package_value) {
            if (is_null($package_value)) {
                $question = null;
                switch ($package_option) {
                    case 'package_name':
                        $question = new Question("What is your package name? [my-package]", "my-package");
                        $question->setNormalizer(function ($value) {
                            $value = str_replace(' ', '-', $value);
                            return strtolower($value);
                        });
                        break;
                    case 'package_description':
                        $question = new Question("What is your package description? [My package description]", "My package description");
                        $question->setNormalizer(function ($value) {
                            $value = str_replace(' ', '-', $value);
                            return strtolower($value);
                        });
                        break;
                    case 'namespace_tool_name':
                        $question = new Question("What is your package namespace? [MyNovaPackage]", "MyNovaPackage");
                        $question->setNormalizer(function ($value) {
                            return str_replace(' ', '', $value);
                        });
                    break;
                }

                if (!is_null($question)) {
                    $this->package[$package_option] = $this->question->ask($input, $output, $question);
                }
            }
        }

        $this->core();

        $output->writeln("Nova scaffolding complete.");
    }

    protected function replacer($file)
    {
        $contents = file_get_contents($file);
        $original = $contents;

        foreach ($this->config as $option => $value) {
            $contents = str_replace(":{$option}", $value, $contents);
        }

        foreach ($this->package as $option => $value) {
            $contents = str_replace(":{$option}", $value, $contents);
        }

        if ($contents != $original) {
            file_put_contents($file, $contents);
        }
    }

    protected function core()
    {
        shell_exec("git clone git@github.com:spatie/skeleton-nova-tool.git .");
        shell_exec("rm -rf .git");
        shell_exec("git init");

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(getcwd()));

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }
            
            $this->replacer($file->getPathname());
        }

        shell_exec("git add .");
        shell_exec("git commit -m 'Initial Commit - Nova CLI'");
    }
}
