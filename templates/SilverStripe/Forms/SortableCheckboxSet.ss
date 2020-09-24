<div $AttributesHTML>
	<h4>$Title</h4>
	<div class="d-flex sortable flex-wrap align-content-stretch">
		<% if $Options.Count %>
			<% loop $Options %>
				<div class="col-3 mb-2 sort-item p-1" role="$Role" data-id="$ID">
					<div class="$Class p-1 border border-light d-flex align-items-center" role="$Role">
						<div class="drag-handle p3"><span class="fas fa-arrows-alt"></span></div>
						<div class="field switch-button switch-button-success my-0 mx-2<% if extraClass %> $extraClass<% end_if %>">
							<input id="$ID" class="checkbox" name="$Name" type="checkbox" value="$Value.ATT"<% if $isChecked %> checked="checked"<% end_if %><% if $isDisabled %> disabled="disabled"<% end_if %> />
							<span>
								<label class="right" for="$ID"></label>
							</span>
						</div>
						<label for="$ID" class="m-0 d-block">$Title</label>
						<% if $Up.AdditionalEditFields %>
							<div class="justify-self-end ml-auto"><a href="javascript:;" data-toggle="modal" data-target="#additional-fields-modal-$ID"><i class="fa fa-cog"></i></a></div>
						<% end_if %>
					</div>
				</div>
				<% if $Up.AdditionalEditFields %>
					<div class="modal fade" id="additional-fields-modal-$ID" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title" id="exampleModalLabel">Additional Settings</h5>
									<a href="#" class="close" data-dismiss="modal" aria-label="Close">
										<span aria-hidden="true">×</span>
									</a>
								</div>
								<div class="modal-body" data-absorb="$Value">
									<!-- // inject the field group here -->
								</div>
								<div class="modal-footer">
									<a href="#" class="btn btn-success" data-dismiss="modal">Save</a>
								</div>
							</div>
						</div>
					</div>
				<% end_if %>
			<% end_loop %>
		<% else %>
			<li><%t SilverStripe\\Forms\\CheckboxSetField_ss.NOOPTIONSAVAILABLE 'No options available' %></li>
		<% end_if %>
	</div>
</div>
