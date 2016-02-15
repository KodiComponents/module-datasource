<style>
    .folder-container.dropable-hover .list-group {
        min-height: 30px;
        border:     2px dashed #ccc;
    }

    .ui-draggable-dragging {
        z-index: 99999;
    }

    .section-draggable {
        cursor: move;
    }

    .folder-container .panel-heading,
    .folder-container .list-group-item {
        padding-top:    9px;
        padding-bottom: 9px;
    }
</style>

<div class="sections-list">
    @foreach ($folders as $folder)
    <div class="folder-container" data-id="{{ $folder->id }}">
        <div class="panel-heading">
            <span class="panel-title" data-icon="folder-open-o">{{ $folder->name }}</span>
            <div class="panel-heading-controls">
                {!! UI::icon('trash', ['class' => 'remove-folder btn btn-xs btn-default']) !!}
            </div>
        </div>
        <div class="list-group no-margin-b">
            @if (count($folder->sections) > 0)
            @foreach ($folder->sections as $section)
            {!! link_to($section->getLink(), $section->name . UI::icon('ellipsis-v fa-lg', [
			'class' => 'pull-right  section-draggable'
		    ]), [
                'data-icon' => $section->getIcon(),
                'data-id' => $section->id,
                'data-folder-id' => $section->folder_id,
                'class' => ($section->id == $currentSection->id) ? 'list-group-item active' : 'list-group-item'
            ]) !!}
			@endforeach
            @endif
        </div>
    </div>
    @endforeach

    <div class="folder-container" data-id="0">
        @if(count($folders) > 0)
        <div class="panel-heading">
            <span class="panel-title" data-icon="folder-open-o">Datasources</span>
        </div>
        @endif
        <div class="list-group no-margin-b">
            @foreach ($sections as $section)
            @if(empty($section->folder_id))
            {!! link_to($section->getLink(), $section->name . UI::icon('ellipsis-v fa-lg', [
                'class' => 'pull-right section-draggable'
                ]), [
                'data-icon' => $section->getIcon(),
                'data-id' => $section->id,
                'data-folder-id' => $section->folder_id,
                'class' => ($section->id == $currentSection->id) ? 'list-group-item active' : 'list-group-item'
            ]) !!}
            @endif
            @endforeach
        </div>
    </div>
</div>