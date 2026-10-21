<?php

namespace Grav\Plugin\Console;

use Grav\Console\ConsoleCommand;
use Grav\Plugin\ComponentLibrary\Usage;
use Symfony\Component\Console\Input\InputOption;

/**
 * bin/plugin component-library usage [--page=/route] [--json]
 *
 * Where the library's components are used on this site: the page templates
 * and theme settings whose forms include them, the pages with content in
 * them, the theme templates that render them, and the pattern templates the
 * theme has its own copy of. The same data as the Admin2 report.
 */
class UsageCommand extends ConsoleCommand
{
    protected function configure(): void
    {
        $this
            ->setName('usage')
            ->setDescription('Show where the library\'s components are used on this site')
            ->addOption('page', 'p', InputOption::VALUE_REQUIRED, 'Only this page (its route, such as /about)')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the result as JSON');
    }

    protected function serve(): int
    {
        require_once __DIR__ . '/../classes/Library.php';
        require_once __DIR__ . '/../classes/Usage.php';

        $this->initializePages();
        $grav = \Grav\Common\Grav::instance();
        $usage = new Usage($grav);
        $io = $this->output;

        $route = $this->input->getOption('page');
        if (\is_string($route) && $route !== '') {
            $page = $grav['pages']->find('/' . ltrim($route, '/'));
            if (!$page) {
                $io->writeln("<red>No page at {$route}</red>");
                return 2;
            }
            $rows = $usage->pageComponents($page);
            if ($this->input->getOption('json')) {
                $io->writeln(json_encode(['route' => $page->rawRoute(), 'template' => $page->template(), 'components' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return 0;
            }
            $io->writeln("<cyan>{$page->rawRoute()}</cyan> (template {$page->template()})");
            foreach ($rows as $c) {
                $state = $c['has_content'] ? '<green>has content</green>' : 'empty';
                $io->writeln(sprintf('  %-28s %-12s %s', $c['id'], $state, $c['tab'] ? "tab: {$c['tab']}" : ''));
            }
            if (!$rows) {
                $io->writeln('  No library components in this template.');
            }
            return 0;
        }

        $report = $usage->report();
        if ($this->input->getOption('json')) {
            $io->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $io->writeln("Theme <cyan>{$report['theme']}</cyan>, {$report['pages_scanned']} pages scanned.\n");
        foreach ($report['components'] as $c) {
            $io->writeln("<cyan>{$c['id']}</cyan> {$c['version']}  {$c['name']}");
            if ($c['templates']) {
                $io->writeln('  Fields in page types: ' . implode(', ', array_map(fn ($t) => $t['template'] . ($t['tab'] ? " ({$t['tab']})" : ''), $c['templates'])));
            }
            if ($c['theme_settings']) {
                $io->writeln('  Fields in theme settings' . ($c['theme_settings']['tab'] ? " ({$c['theme_settings']['tab']})" : '') . ($c['theme_settings']['configured'] ? ', filled in' : ', not filled in'));
            }
            if ($c['templates']) {
                $io->writeln('  Pages with content: ' . (\count($c['pages']) ?: 'none') . ($c['pages'] ? ' (' . implode(', ', array_map(fn ($p) => $p['route'], \array_slice($c['pages'], 0, 8))) . (\count($c['pages']) > 8 ? ', ...' : '') . ')' : ''));
            }
            if ($c['rendered_by']) {
                $io->writeln('  Rendered by: ' . implode(', ', $c['rendered_by']));
            }
            foreach ($c['own_copies'] as $copy) {
                $io->writeln("  Theme's own copy: {$copy['file']}" . ($copy['identical'] ? ' (identical to the library\'s)' : ' (customized)'));
            }
        }
        if ($report['unused']) {
            $io->writeln("\nNot used: " . implode(', ', array_map(fn ($u) => $u['id'], $report['unused'])));
        }

        return 0;
    }
}
