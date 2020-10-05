
<!-- ============================================================== -->
<!-- pageheader -->
<!-- ============================================================== -->
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
		<div class="page-header">
			<h2 class="pageheader-title">$Title</h2>
			<div class="page-breadcrumb">
				<nav aria-label="breadcrumb">
					<ol class="breadcrumb">
						<li class="breadcrumb-item"><a href="$Dashboard.Link" class="breadcrumb-link">Dashboard</a></li>
						<li class="breadcrumb-item"><a href="$Link" class="breadcrumb-link">Logs</a></li>
						<li class="breadcrumb-item active"><a href="javascript:;" class="breadcrumb-link">$currentRecord.Title</a></li>
					</ol>
				</nav>
			</div>
		</div>
	</div>
</div>
<!-- ============================================================== -->
<!-- end pageheader -->
<!-- ============================================================== -->

<div class="card">
	<h5 class="card-header">Server Job:: {$currentRecord.CallClass}::{$currentRecord.CallMethod} <a href="$Link" class="btn btn-danger btn-sm float-right" role="button">Cancel</a></h5>
	<div class="card-body">
		<div><strong>ID: </strong>$currentRecord.ID</div>
		<div><strong>Name: </strong>$currentRecord.Name</div>
		<div><strong>Date: </strong>$currentRecord.Created.Nice</div>
		<div><strong>Status: </strong>$currentRecord.Status</div>
	</div>
</div>
	
<div class="card">
	<h5 class="card-header">Arguments</h5>
	<div class="card-body">
		<div><pre class="m-0 p-2"><xmp class="m-0">$currentRecord.Args</xmp></pre></div>
	</div>
</div>
	
<div class="card">
	<h5 class="card-header">Logs</h5>
	<div class="card-body">
		<pre class="m-0 p-2">
			<xmp class="m-0">
				$currentRecord.Logs
			</xmp>
		</pre>
	</div>
</div>

