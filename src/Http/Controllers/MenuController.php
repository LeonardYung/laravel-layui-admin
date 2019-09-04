<?php

namespace Moell\LayuiAdmin\Http\Controllers;


use Illuminate\Http\Request;
use Moell\LayuiAdmin\Http\Requests\Menu\CreateOrUpdateRequest;
use Moell\LayuiAdmin\Models\Navigation;
use Moell\LayuiAdmin\Resources\Menu as MenuResource;
use Auth;
use SMartins\PassportMultiauth\Config\AuthConfigHelper;

class MenuController extends Controller
{
    /**
     * @author moell<moell91@foxmail.com>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $menus = Navigation::query()
            ->where('guard_name', $request->input('guard_name', 'admin'))
            ->orderBy('sequence', 'desc')
            ->get()
            ->toJson();

        return view("admin::menu.index", compact("menus"));
    }
    /**
     * @author moell<moell91@foxmail.com>
     * @param CreateOrUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateOrUpdateRequest $request)
    {
        Navigation::create($request->all());

        return $this->created();
    }

    /**
     * @author moell<moell91@foxmail.com>
     * @return \Illuminate\Http\JsonResponse
     */
    public function my()
    {
        $guardName = AuthConfigHelper::getUserGuard(Auth::user());

        $userPermissions = Auth::user()->getAllPermissions()->pluck('name');
        $menus = Navigation::query()
            ->where('guard_name', $guardName)
            ->orderBy('sequence', 'desc')
            ->get()
            ->filter(function ($item) use ($userPermissions) {
                return !$item->permission_name || $userPermissions->contains($item->permission_name);
            });

        return response()->json(['data' => make_tree($menus->toArray())]);
    }

    /**
     * @author moell<moell91@foxmail.com>
     * @param CreateOrUpdateRequest $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update(CreateOrUpdateRequest $request, $id)
    {
        $menu = Navigation::query()->findOrFail($id);

        $menu->update($request->toArray());

        return $this->noContent();
    }

    /**
     * @author moell<moell91@foxmail.com>
     * @param $id
     * @return MenuResource
     */
    public function show($id)
    {
        return new MenuResource(Navigation::query()->findOrFail($id));
    }

    /**
     * @author moell<moell91@foxmail.com>
     * @param $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $menu = Navigation::query()->findOrFail($id);

        if (Navigation::query()->where('parent_id', $menu->id)->count()) {
            return $this->unprocesableEtity([
                'parent_id' => 'Please delete the submenu first.'
            ]);
        }

        $menu->delete();

        return $this->noContent();
    }
}