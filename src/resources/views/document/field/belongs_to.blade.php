<div class="form-group">
	<label class="control-label col-md-2 col-sm-3" for="{{ $key }}">
		{{ $name }}
	</label>

	<div class="col-md-10 col-sm-9">
		<relation-bt relation="{{ $field->getRelationFieldName() }}" key="{{ $key }}"></relation-bt>
	</div>
</div>

<!-- template for child -->
<template id="bt-template">
	<div class="panel">
		<div class="panel-heading">
			<h3 class="panel-title">@{{ section.name }}</h3>
		</div>
		<div class="panel-body" v-if="!record.id">
			<select type="text" class="form-control"></select>
		</div>
		<table class="table table-info" v-if="record.id">
			<colgroup>
				<col width="50px">
				<col>
				<col width="50px">
			</colgroup>
			<thead>
			<tr>
				<th>ID</th>
				<th>Title</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td>
					@{{ record.id }}
					<input type="hidden" name="{{ $key }}" value="@{{ record.id }}" />
				</td>
				<th>
					<a href="@{{ record.url }}" class="popup">@{{ record.title }}</a>
				</th>
				<th>
					<button type="button" class="btn btn-xs btn-danger" @click="removeRecord(record)">
					<i class="fa fa-times"></i>
					</button>
				</th>
			</tr>
			</tbody>
		</table>
		<div class="panel-footer">
			{!! link_to_route('backend.datasource.document.create', trans('datasource::fields.has_one.create_document'), [$relatedSection->getId()], [
                'data-icon' => 'plus',
                'class' => 'btn btn-success btn-labeled popup fancybox.iframe',
                'data-target' => $key
            ]) !!}
		</div>
	</div>
</template>