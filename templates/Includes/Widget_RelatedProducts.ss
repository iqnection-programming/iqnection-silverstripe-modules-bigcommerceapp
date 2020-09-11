
{{#if products}}
<div class="widget-related-products">
	{{#if widget_title}}<h3>{{widget_title}}</h3>{{/if}}
	<div class="products-grid">
		{{#each products}}
			<div class="productCard">
				<a href="{{url}}">{{title}}</a>
			</div>
		{{/each}}
	</div>
</div>
{{/if}}