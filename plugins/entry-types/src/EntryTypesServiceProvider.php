<?php

namespace Plugins\EntryTypes;

use App\Models\Entry;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Michelf\MarkdownExtra;

class EntryTypesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Need to call this in the `register()` (rather than `boot()`) method in order to register our routes before
        // "core's" catch-all page route.
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
        $this->registerHooks(); // For these, it doesn't really matter.
    }

    protected function registerHooks(): void
    {
        /**
         * Adds short-form entry types.
         *
         * @param  array  $types Rewgistered types.
         * @return array  $types Filtered array of types.
         */
        add_filter('entries.registered_types', function ($types) {
            $types['note'] = ['icon' => 'mdi mdi-message-outline'];
            $types['like'] = ['icon' => 'mdi mdi-star-outline'];

            return $types;
        });

        /**
         * Sets autogenerated names, for these "short-form" entry types.
         *
         * @param  string  $name  Original entry name.
         * @param  Entry   $entry Entry being saved.
         * @return string         Filtered name.
         */
        add_filter('entries.set_name', function ($name, $entry) {
            if (! in_array($entry->type, ['note', 'like'], true)) {
                return $name;
            }

            // Ensure notes, likes, and listens get a name based off their content.
            $parser = new MarkdownExtra();
            $parser->no_markup = false; // Do not escape markup already present.

            $content = $parser->defaultTransform($entry->content);
            $content = trim(strip_tags($content));

            $name = Str::words($content, 10, ' …');

            // Decode quotes, etc. (We escape on output.)
            $name = html_entity_decode($name, ENT_HTML5, 'UTF-8');
            $name = preg_replace('~\s+~', ' ', $name); // Get rid of excess whitespace.
            $name = Str::limit($name, 250, '…'); // Shorten (again).

            return $name;
        }, 20, 2);

        /**
         * Bypasses the default slug generation process.
         *
         * @param  string      $slug  Whether to bypass "core" behavior. Default `null`.
         * @param  Entry       $entry Entry being saved.
         * @return string|null        Autogenerated slug, or `null`.
         */
        add_filter('entries.set_slug', function ($slug, $entry) {
            if (! in_array($entry->type, ['note', 'like'], true)) {
                return $slug;
            }

            if (! empty($entry->slug)) {
                // A slug was set previously; let "core" do its thing.
                return $slug;
            }

            // Ensure notes, likes, and listens get a random slug rather than a title-based one.
            return random_slug();
        }, 20, 2);
    }
}
