<?php

namespace VoyagerChineseLang\Policies;

use TCG\Voyager\Models\Menu;
use TCG\Voyager\Contracts\User;

class MenuPolicy
{
    public function browse(User $user, $model)
    {
        return true;
    }
    public function edit(User $user, $menu)
    {
        return $menu->name != 'admin';
    }

    public function add()
    {
        return true;
    }
    public function delete(User $user, $menu)
    {
        return $menu->name != 'admin-zh-CN';
    }
}