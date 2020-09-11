<!-- ============================================================== -->
<!-- pageheader -->
<!-- ============================================================== -->
<div class="row">
  <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
    <div class="page-header">
      <h2 class="pageheader-title">Log File {$CurrentLog.filename}</h2>
      <div class="page-breadcrumb">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="$Dashboard.Link" class="breadcrumb-link">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="$Link" class="breadcrumb-link">BigCommerce Logs</a></li>
            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">Log File {$CurrentLog.filename}</a></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
    
    <pre><xmp>
      {$CurrentLog.filedata.RAW}
      </xmp>
    </pre>
      
  </div>
</div>