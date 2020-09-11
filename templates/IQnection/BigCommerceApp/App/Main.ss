<!doctype html>
<html lang="en">
 
<head>
  <% base_tag %>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><% if $Metatitle %>$Metatitle<% else %>$Title<% end_if %></title>
    
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
                <div class="collapse navbar-collapse " id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto navbar-right-top">
                        <li class="nav-item dropdown notification">
                            <a class="nav-link nav-icons" href="#" id="navbarDropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <i class="fas fa-fw fa-bell"></i>
                              <% if $CurrentUser.Notifications.Filter(Status,New).Count %>
                                <span class="indicator"></span>
                              <% end_if %>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-right notification-dropdown">
                                <li>
                                    <div class="notification-title"> Notification</div>
                                    <div class="notification-list">
                                        <div class="list-group">
                                          <% if $CurrentUser.Notifications.Filter(Status,New).Count %>
                                            <% loop $CurrentUser.Notifications.Filter(Status,New).Limit(5) %>
                                            <div href="$Link" class="list-group-item">
                                                <div class="notification-info">
                                                    <div class="notification-list-user-block">
                                                      $Message.RAW
                                                        <div class="notification-date">$Created.Ago(0)</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <% end_loop %>
                                          <% else %>
                                            <div class="list-group-item list-group-item-action">
                                                <div class="notification-info">
                                                    <div class="notification-list-user-block">
                                                        All Clear
                                                    </div>
                                                </div>
                                            </div>
                                          <% end_if %>
                                        </div>
                                    </div>
                                </li>
                                <% if $CurrentUser.Notifications.Exclude(Status,Dismissed).Count %>
                                <li>
                                    <div class="list-footer"> <a href="$Top.Dashboard.Link">View all notifications</a></div>
                                </li>
                                <% end_if %>
                            </ul>
                        </li>
                        
						<li class="nav-item new-window">
							<a href="{$Dashboard.Link}?sess=$CurrentMember.TempIDHash" class="nav-link" target="_blank"><i class="fa fa-external-link-alt"></i></a>
						</li>
						
                        <li class="nav-item dropdown nav-user">
                            <a class="nav-link nav-user-img" href="#" id="navbarDropdownMenuLink2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <span class="letter">$CurrentUser.FirstLetter</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLink2">
                              <div class="nav-user-info">
                                  <h5 class="mb-0 text-white nav-user-name">$CurrentUser.Fullname</h5>
                              </div>
                              <a class="dropdown-item" href="/admin/"><i class="fas fa-key mr-2"></i>Admin</a>
                              <a class="dropdown-item" href="/_bc/auth/logout"><i class="fas fa-power-off mr-2"></i>Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
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
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav flex-column">
                            <li class="nav-divider">
                                Menu
                            </li>
							<% loop $Dashboard.Menu %>
								<li class="nav-item">
									<% if $Children.Count %>
                    					<% if $Active %>
										  <a class="nav-link active" href="$Link" data-toggle="collapse" aria-expanded="true" data-target="#submenu-{$ID}" aria-controls="submenu-{$ID}"<% if $Target %> target="$Target"<% end_if %>><i class="fa fa-fw fa-{$Icon}"></i>$Title</a>
                    					<% else %>
										  <a class="nav-link" href="$Link" data-toggle="collapse" aria-expanded="false" data-target="#submenu-{$ID}" aria-controls="submenu-{$ID}"<% if $Target %> target="$Target"<% end_if %>><i class="fa fa-fw fa-{$Icon}"></i>$Title</a>
                    					<% end_if %>
										<div id="submenu-{$ID}" class="collapse submenu<% if $Active %> show<% end_if %>" style="">
											<ul class="nav flex-column">
												<% loop $Children %>
													<% if $Children.Count %>
														<li class="nav-item">
															<a class="nav-link<% if $Active %> active<% end_if %>" href="{$Link}" <% if $Target %> target="$Target"<% end_if %> data-toggle="collapse" aria-expanded="false" data-target="#submenu-{$ID}-{$Children.First.ID}" aria-controls="submenu-{$ID}-{$Children.First.ID}">$Title</a>
															<div id="submenu-{$ID}-{$Children.First.ID}" class="collapse submenu" style="">
																<ul class="nav flex-column">
																	<% loop $Children %>
																		<li class="nav-item">
																			<a class="nav-link<% if $Active %> active<% end_if %>" href="{$link}" <% if $Target %> target="$Target"<% end_if %>>$Title</a>
																		</li>
																	<% end_loop %>
																</ul>
															</div>
														</li>
													<% else %>
														<li class="nav-item">
															<a class="nav-link<% if $Active %> active<% end_if %>" href="{$Link}" <% if $Target %> target="$Target"<% end_if %>>$Title</a>
														</li>
													<% end_if %>
												<% end_loop %>
											</ul>
										</div>
									<% else %>
										<li class="nav-item ">
											<a class="nav-link<% if $Active %> active<% end_if %>" href="{$Link}" <% if $Target %> target="$Target"<% end_if %>><i class="fa fa-fw fa-{$Icon}"></i>$Title</a>
										</li>
									<% end_if %>
								</li>
							<% end_loop %>
							<li class="nav-item ">
								<a class="nav-link" href="/admin/" target="$Target"><i class="fa fa-fw fa-lock"></i>SilverStripe Admin</a>
							</li>
                        </ul>
                    </div>
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
			<span class="dashboard-spinner spinner-lg" id="page-loading"></span>
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
<template id="spinner-template">
  <span data-spinner class="dashboard-spinner spinner-secondary spinner-xs"></span>
</template>
<template id="loading-template">
  <div class="loading-overlay">
  	<div class="spinner-container">
	  <span data-spinner class="dashboard-spinner spinner-secondary spinner-xs"></span>
	</div>
  </div>
</template>
</body>
 
</html>
