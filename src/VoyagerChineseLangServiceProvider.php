<?php

namespace VoyagerChineseLang;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use TCG\Voyager\Models\Menu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VoyagerChineseLangServiceProvider extends ServiceProvider
{
   

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        app(Dispatcher::class)->listen('voyager.menu.display', function ($menu) {
            $this->convertChinese($menu);
        });
    }

      /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    protected function convertChinese(Menu  $menu)
    {
        if($menu->name == 'admin'){
            $menuName = 'admin-zh-CN';
            $menu_cn = Menu::where('name', '=', $menuName)
            ->with(['parent_items.children' => function ($q) {
                $q->orderBy('order');
            }])
            ->first();
            $items = $menu_cn->parent_items->sortBy('order');
            $items = self::processItems($items);
            $menu->parent_items = $items;
        }
    }

    public static function processItems($items)
    {
        $items = $items->transform(function ($item) {
            // Translate title
            $item->title = $item->getTranslatedAttribute('title');
            // Resolve URL/Route
            $item->href = $item->link(true);

            if ($item->href == url()->current() && $item->href != '') {
                // The current URL is exactly the URL of the menu-item
                $item->active = true;
            } elseif (Str::startsWith(url()->current(), Str::finish($item->href, '/'))) {
                // The current URL is "below" the menu-item URL. For example "admin/posts/1/edit" => "admin/posts"
                $item->active = true;
            }
            if (($item->href == url('') || $item->href == route('voyager.dashboard')) && $item->children->count() > 0) {
                // Exclude sub-menus
                $item->active = false;
            } elseif ($item->href == route('voyager.dashboard') && url()->current() != route('voyager.dashboard')) {
                // Exclude dashboard
                $item->active = false;
            }

            if ($item->children->count() > 0) {
                $item->setRelation('children', static::processItems($item->children));

                if (!$item->children->where('active', true)->isEmpty()) {
                    $item->active = true;
                }
            }

            return $item;
        });

        // Filter items by permission
        $items = $items->filter(function ($item) {
            return !$item->children->isEmpty() || Auth::user()->can('browse', $item);
        })->filter(function ($item) {
            // Filter out empty menu-items
            if ($item->url == '' && $item->route == '' && $item->children->count() == 0) {
                return false;
            }

            return true;
        });

        return $items->values();
    }
}