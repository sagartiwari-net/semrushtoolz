<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreToolServerRequest;
use App\Http\Requests\Admin\UpdateToolServerRequest;
use App\Models\ToolAccessGroup;
use App\Models\ToolAccessServer;
use Illuminate\Http\Request;

class ToolServerController extends Controller
{
    public function index()
    {
        $groups = ToolAccessGroup::with(['servers' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('admin.tool-servers.index', compact('groups'));
    }

    public function create()
    {
        $groups = ToolAccessGroup::orderBy('sort_order')->get();

        return view('admin.tool-servers.form', [
            'server' => new ToolAccessServer(['type' => 'proxy', 'is_active' => true]),
            'groups' => $groups,
        ]);
    }

    public function store(StoreToolServerRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        ToolAccessServer::create($data);

        return redirect()->route('admin.tool-servers.index')
            ->with('success', 'Access server added.');
    }

    public function edit(ToolAccessServer $toolServer)
    {
        $groups = ToolAccessGroup::orderBy('sort_order')->get();

        return view('admin.tool-servers.form', [
            'server' => $toolServer,
            'groups' => $groups,
        ]);
    }

    public function update(UpdateToolServerRequest $request, ToolAccessServer $toolServer)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $toolServer->update($data);

        return redirect()->route('admin.tool-servers.index')
            ->with('success', 'Access server updated.');
    }

    public function destroy(ToolAccessServer $toolServer)
    {
        $toolServer->delete();

        return back()->with('success', 'Access server removed.');
    }

    public function toggle(ToolAccessServer $toolServer)
    {
        $toolServer->update(['is_active' => ! $toolServer->is_active]);

        return back()->with('success', 'Server status updated.');
    }
}
