<!doctype html>
<html lang="en">
 
<head>
  <% base_tag %>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>$Title</title>
    
     <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">
	<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#e30000">
	<meta name="msapplication-TileColor" content="#ffc40d">
	<meta name="theme-color" content="#e30000">
</head>

<body>
    <!-- ============================================================== -->
    <!-- main wrapper -->
    <!-- ============================================================== -->
    <div class="dashboard-main-wrapper">
         <!-- ============================================================== -->
        <!-- navbar -->
        <!-- ============================================================== -->
         <div class="dashboard-header">
            <nav class="navbar navbar-expand-lg bg-white fixed-top">
                <a class="navbar-brand" href="$Dashboard.Link"><% include LogoSVG %></a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </nav>
        </div>
        <!-- ============================================================== -->
        <!-- end navbar -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- left sidebar -->
        <!-- ============================================================== -->
      <div class="nav-left-sidebar sidebar-dark">
            <div class="menu-list">
                <nav class="navbar navbar-expand-lg navbar-light">
                    <a class="d-xl-none d-lg-none" href="#">Dashboard</a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                </nav>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- end left sidebar -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- wrapper  -->
        <!-- ============================================================== -->
        <div class="dashboard-wrapper">
            <div class="container-fluid dashboard-content">
              
              <div class="row">
                  <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
					  <div id="alert-container" class="pb-2 px-4">
						<% if $Alerts.Count %>
						  <% loop $Alerts %>
							<div class="alert alert-{$Status} alert-dismissible fade show mt-2 mb-0" role="alert">
							  <span class="message">$Message</span>
							  <a href="#" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">×</span>
							  </a>
							</div> 
						  <% end_loop %>
						<% end_if %>
					  </div>
					  
	                     $Layout
                  </div>
              </div>
            </div>
			
			<%--
			<!-- NO FOOTER NEEDED, BUT IT'S HERE IF YOU WANT IT -->
            <!-- ============================================================== -->
            <!-- footer -->
            <!-- ============================================================== -->
            <div class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                            Copyright © 2018 Concept. All rights reserved. Dashboard by <a href="https://colorlib.com/wp/">Colorlib</a>.
                        </div>
                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                            <div class="text-md-right footer-links d-none d-sm-block">
                                <a href="javascript: void(0);">About</a>
                                <a href="javascript: void(0);">Support</a>
                                <a href="javascript: void(0);">Contact Us</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ============================================================== -->
            <!-- end footer -->
            <!-- ============================================================== -->
			--%>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- end main wrapper -->
    <!-- ============================================================== -->

<div class="modal fade" id="genericModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <a href="#" class="close" data-dismiss="modal">
            <span aria-hidden="true">&times;</span>
        </a>
      </div>
      <div class="modal-body">
        
      </div>
    </div>
  </div>
</div>

<template id="alert-template">
  <div class="alert alert-dismissible fade show mt-2 mb-0" role="alert">
    <span class="message"></span>
    <a href="#" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">×</span>
    </a>
  </div>  
</template>
<template id="spinner">
  <span data-spinner class="dashboard-spinner spinner-secondary spinner-xs"></span>
</template>
</body>
 
</html>
