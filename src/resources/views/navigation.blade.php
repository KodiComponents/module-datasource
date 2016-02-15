<div class="navigation panel">
	@if (!empty($types))
	<div class="compose-btn panel-heading">
		<div class="btn-group">
			{!! HTML::link('#', trans('datasource::core.button.create'), [
				'class' => 'dropdown-toggle btn btn-primary btn-labeled btn-block',
				'data-icon' => 'plus',
				'data-icon-append' => 'caret-down',
				'data-toggle' => 'dropdown'
			]) !!}
			<ul class="dropdown-menu">
				@foreach ($types as $type => $object)
				<li>
					{!! HTML::link($object->getLink(), $object->getTitle(), [
						'data-icon' => $object->getIcon()
					]) !!}
				</li>
				@endforeach
			</ul>
		</div>

		<br /><br />
		{!! HTML::link('#', trans('datasource::core.button.create_folder'), [
			'class' => 'btn btn-default btn-labeled btn-xs create-folder-button',
			'data-icon' => 'plus'
		]) !!}
	</div>
	@endif

	@include('datasource::sections-list')
</div>

<div id="folder-modal" class="modal fade" tabindex="-1" role="dialog" style="display: none;">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="no-margin-vr"><?php echo __('New folder'); ?></h4>
			</div>
			<form action="#" method="post">
				<div class="modal-body">
					<div class="form-group">
						<label class="control-label" for="category-title"><?php echo __('Folder name'); ?></label>
						<div class="controls">
							<input type="text" name="folder-name" class="form-control" />
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<a href="#" class="btn" data-dismiss="modal" aria-hidden="true"><?php echo __('Cancel'); ?></a>
					<button class="create-folder-btn btn btn-primary"><?php echo __('Save'); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>