<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreToolGroupRequest;
use App\Http\Requests\Admin\UpdateToolGroupRequest;
use App\Models\Tool;
use App\Models\ToolAccessGroup;

class ToolGroupController extends Controller
{
    public function index()
    {
        $groups = ToolAccessGroup::with('tool')->orderBy('sort_order')->get();

        return view('admin.tool-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.tool-groups.form', [
            'group' => new ToolAccessGroup(['is_active' => true]),
            'tools' => Tool::orderByDesc('is_active')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreToolGroupRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $tool = Tool::find($data['tool_id']);
        $data['grant'] = $tool?->slug ?? $data['slug'];

        ToolAccessGroup::create($data);

        return redirect()->route('admin.tool-groups.index')->with('success', 'Tool group created.');
    }

    public function edit(ToolAccessGroup $toolGroup)
    {
        return view('admin.tool-groups.form', [
            'group' => $toolGroup,
            'tools' => Tool::orderByDesc('is_active')->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateToolGroupRequest $request, ToolAccessGroup $toolGroup)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $tool = Tool::find($data['tool_id']);
        $data['grant'] = $tool?->slug ?? $data['slug'];

        $toolGroup->update($data);

        return redirect()->route('admin.tool-groups.index')->with('success', 'Tool group updated.');
    }
}
