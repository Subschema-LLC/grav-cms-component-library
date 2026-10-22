<?php

namespace Grav\Plugin\Console;

use Grav\Console\ConsoleCommand;
use Grav\Plugin\ComponentLibrary\Library;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * bin/plugin component-library lint [user/themes/mytheme]
 *
 * Runs the template checks from patterns/system/template-hygiene on every
 * .twig file under a path (or the active theme when omitted). Exit code 1
 * when any error is found (or any warning, with --strict).
 */
class LintCommand extends ConsoleCommand
{
    protected function configure(): void
    {
        $this
            ->setName('lint')
            ->setDescription('Check Twig templates for the problems documented in system/template-hygiene')
            ->addArgument('path', InputArgument::OPTIONAL, 'A theme or plugin folder, or one template file (defaults to the active theme)')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Fail on warnings too')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the findings as JSON');
    }

    protected function serve(): int
    {
        require_once __DIR__ . '/../classes/Library.php';

        $gravRoot = \defined('GRAV_ROOT') ? (string) \constant('GRAV_ROOT') : (string) getcwd();
        $path = trim((string) ($this->input->getArgument('path') ?? ''));
        if ($path === '') {
            try {
                $this->initializeThemes();
                $grav = \Grav\Common\Grav::instance();
                $found = $grav['locator']->findResource('theme://');
                if (\is_string($found) && $found !== '') {
                    $path = $found;
                } else {
                    $theme = (string) $grav['config']->get('system.pages.theme', '');
                    if ($theme !== '') {
                        $path = $gravRoot . '/user/themes/' . $theme;
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        if ($path !== '' && !file_exists($path) && file_exists($gravRoot . '/' . $path)) {
            $path = $gravRoot . '/' . $path;
        }
        if ($path === '' || !file_exists($path)) {
            $this->output->writeln("<red>Not found:</red> " . ($path !== '' ? $path : 'active theme'));
            return 2;
        }

        $issues = (new Library())->lintPath($path);

        if ($this->input->getOption('json')) {
            $this->output->writeln(json_encode($issues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $base = rtrim(realpath(is_dir($path) ? $path : \dirname($path)), '/') . '/';
            foreach ($issues as $i) {
                $color = $i['level'] === 'error' ? 'red' : 'yellow';
                $file = str_replace($base, '', realpath($i['file']) ?: $i['file']);
                $this->output->writeln("<{$color}>{$i['level']}</{$color}> {$file}:{$i['line']} <cyan>{$i['rule']}</cyan> {$i['message']}");
            }
            $errors = \count(array_filter($issues, fn ($i) => $i['level'] === 'error'));
            $warnings = \count($issues) - $errors;
            $this->output->writeln($issues ? "\n{$errors} error(s), {$warnings} warning(s)." : '<green>No problems found.</green>');
        }

        $failing = array_filter($issues, fn ($i) => $i['level'] === 'error' || $this->input->getOption('strict'));

        return $failing ? 1 : 0;
    }
}
