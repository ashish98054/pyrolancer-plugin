<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Redirect;
use ActivComponent;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Ahoy\Pyrolancer\Models\Favorite as FavoriteModel;
use ApplicationException;

class Favorites extends ActivComponent
{
    use \Ahoy\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Favorites Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function isPublic()
    {
        return !!$this->param('key');
    }

    public function isOwner()
    {
        return !$this->isPublic() && $this->hasList();
    }

    public function hasList()
    {
        return !!($list = $this->favoriteList()) && !!$list->workers->count();
    }

    public function favoriteList()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            return ($listKey = $this->param('key'))
                ? FavoriteModel::listFromKey($listKey)
                : FavoriteModel::listFromUser(Auth::getUser());
        });
    }

    public function isFavorited($worker)
    {
        if (!$list = $this->favoriteList()) {
            return false;
        }

        return $list->workers->contains($worker);
    }

    public function onToggleFavorite()
    {
        if ((!$id = post('id')) || (!$worker = WorkerModel::find($id))) {
            throw new ApplicationException('Action failed!');
        }

        $list = $this->findOrFirstFavoriteList();

        if ($list->workers->contains($worker)) {
            $list->workers()->remove($worker);
            $isFavorited = 0;
        }
        else {
            $list->workers()->add($worker);
            $isFavorited = 1;
        }

        $this->page['isFavorited'] = $isFavorited;
        $this->page['worker'] = $worker;
        $this->page['mode'] = 'view';
    }

    public function onEmptyList()
    {
        if (!$list = $this->favoriteList()) {
            return false;
        }

        // Empty the list
        $list->workers()->sync([]);

        return Redirect::refresh();
    }

    protected function findOrFirstFavoriteList()
    {
        return $this->favoriteList() ?: FavoriteModel::createList(Auth::getUser());
    }

}