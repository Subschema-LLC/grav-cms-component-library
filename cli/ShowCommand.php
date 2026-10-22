<?php

namespace Grav\Plugin\Console;

use Grav\Console\ConsoleCommand;
use Grav\Plugin\ComponentLibrary\Library;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * bin/plugin component-library show [pattern] [--level=...] [--search=...] [--copy] [--json]
 *
 * Lists or searches the library's patterns when called without a pattern id,
 * or prints one pattern's summary, import@ and include snippets (plugin mode
 * or --copy mode), stored keys and verification steps.
 */
class ShowCommand extends ConsoleCommand
{
    protected function configure(): void
    {
        $this
            ->setName('show')
            ->setDescription('List or search patterns, or show one pattern\'s import@, include and verification steps')
            ->addArgument('pattern', InputArgument::OPTIONAL, 'Pattern id (such as page/tiles) or short name (such as tiles); omit to list patterns')
            ->addOption('level', 'l', InputOption::VALUE_REQUIRED, 'Only patterns at this level (theme, page, page-type, system)')
            ->addOption('search', 's', InputOption::VALUE_REQUIRED, 'Filter patterns by keyword across id, name, summary, use_when and keys')
            ->addOption('copy', null, InputOption::VALUE_NONE, 'Show copy-mode instructions and snippets (without installing the plugin)')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the result as JSON');
    }

    protected function serve(): int
    {
        require_once __DIR__ . '/../classes/Library.php';

        $lib = new Library();
        $io = $this->output;
        $query = (string) ($this->input->getArgument('pattern') ?? '');

        if (trim($query) === '') {
            $level = $this->input->getOption('level');
            $search = $this->input->getOption('search');
            $entries = $lib->search(\is_string($level) ? $level : null, \is_string($search) ? $search : null);

            if ($this->input->getOption('json')) {
                $io->writeln(json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return 0;
            }
            if (!$entries) {
                $io->writeln('<yellow>No matching patterns found.</yellow>');
                return 1;
            }

            $titles = [
                'theme' => 'Theme level (site-wide settings in blueprints.yaml)',
                'page' => 'Page level (per-page content blocks)',
                'page-type' => 'Page types (whole page templates)',
                'system' => 'System level (Grav 2 & plugin practices)',
            ];
            foreach ($titles as $lvl => $title) {
                $group = array_filter($entries, static fn ($e) => $e['level'] === $lvl);
                if (!$group) {
                    continue;
                }
                $io->writeln("<yellow>{$title}</yellow>");
                foreach ($group as $e) {
                    $io->writeln(sprintf('  <cyan>%-28s</cyan> %s — %s', $e['id'], $e['name'], $e['summary']));
                }
                $io->writeln('');
            }
            $io->writeln('Run <cyan>bin/plugin component-library show <pattern></cyan> for its import@, include and verification steps (add <cyan>--copy</cyan> if copying into a theme).');

            return 0;
        }

        $entry = $lib->find($query);
        if ($entry === null) {
            $suggestions = $lib->search(null, $query);
            $io->writeln("<red>Unknown pattern:</red> {$query}");
            if ($suggestions) {
                $io->writeln('Matching patterns: ' . implode(', ', array_map(static fn ($s) => "<cyan>{$s['id']}</cyan>", $suggestions)));
            } else {
                $io->writeln('Run <cyan>bin/plugin component-library show</cyan> to list all patterns.');
            }
            return 2;
        }

        $copy = (bool) $this->input->getOption('copy');
        if ($copy) {
            $entry['copy_mode'] = $lib->copyMode($entry);
        }

        if ($this->input->getOption('json')) {
            $io->writeln(json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $gravVersions = implode(', ', (array) ($entry['compatibility']['grav'] ?? []));
        $io->writeln("<cyan>{$entry['id']}</cyan> v{$entry['version']} — {$entry['name']} ({$entry['status']}, Grav {$gravVersions})");
        $io->writeln($entry['summary']);

        if (!empty($entry['use_when'])) {
            $io->writeln("\n<yellow>Use when:</yellow>");
            foreach ($entry['use_when'] as $u) {
                $io->writeln("  - {$u}");
            }
        }
        if (!empty($entry['avoid_when'])) {
            $io->writeln("\n<yellow>Skip when:</yellow>");
            foreach ($entry['avoid_when'] as $a) {
                $io->writeln("  - {$a}");
            }
        }

        $req = $entry['requires'] instanceof \stdClass ? (array) $entry['requires'] : (array) ($entry['requires'] ?? []);
        if ($req) {
            $parts = [];
            foreach ((array) ($req['patterns'] ?? []) as $p) {
                $parts[] = "pattern {$p}";
            }
            foreach ((array) ($req['plugins'] ?? []) as $p) {
                $parts[] = "plugin {$p}";
            }
            if (!empty($req['php'])) {
                $parts[] = 'PHP in theme/plugin class';
            }
            if ($parts) {
                $io->writeln("\n<yellow>Requires:</yellow> " . implode(', ', $parts));
            }
        }

        if ($copy && !empty($entry['copy_mode']['copy_files'])) {
            $io->writeln("\n<yellow>Copy into your theme:</yellow>");
            foreach ($entry['copy_mode']['copy_files'] as $f) {
                $io->writeln("  {$f['from']} -> {$f['to']}");
            }
        }

        $apply = $copy ? $entry['copy_mode']['apply'] : (array) ($entry['apply'] ?? []);
        $applyHeading = $copy ? 'Apply (without the plugin — copy mode):' : 'Apply:';
        $io->writeln("\n<yellow>{$applyHeading}</yellow>");
        foreach ($apply as $kind => $snippet) {
            $label = ['php' => 'PHP or shell'][$kind] ?? ucfirst((string) $kind);
            $io->writeln("  <cyan>[{$label}]</cyan>");
            foreach (explode("\n", rtrim((string) $snippet)) as $line) {
                $io->writeln("  {$line}");
            }
        }

        if (!empty($entry['data']['keys'])) {
            $keys = array_keys((array) $entry['data']['keys']);
            $io->writeln("\n<yellow>Stored data</yellow> ({$entry['data']['scope']}): " . implode(', ', $keys));
        }
        if (!empty($entry['classes']['slots'])) {
            $io->writeln('<yellow>Class slots:</yellow> ' . implode(', ', (array) $entry['classes']['slots']));
        }

        if (!empty($entry['verify'])) {
            $io->writeln("\n<yellow>Verify:</yellow>");
            foreach ($entry['verify'] as $v) {
                $io->writeln("  - [ ] {$v}");
            }
        }

        return 0;
    }
}
