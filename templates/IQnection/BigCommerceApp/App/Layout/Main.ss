<!-- ============================================================== -->
<!-- pageheader -->
<!-- ============================================================== -->
<div class="row">
  <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
    <div class="page-header">
      <h2 class="pageheader-title">Dashboard</h2>
      <div class="page-breadcrumb">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">Dashboard</a></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
    
    <div class="card">
      <div class="card-header p-4 d-flex justify-content-between">
        <h2 class="m-0">Notifications</h2>
		<% if $ActiveNotifications.Count %>
			<div><a href="$join_links($Link,dismissnotifications)" class="btn btn-outline-primary">Dismiss All</a></div>
		<% end_if %>
      </div>
      <div class="card-body">
        <% if $ActiveNotifications.Count %>
          <div class="table-responsive">
            <table class="table table-striped table-bordered first">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Message</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <% loop $ActiveNotifications %>
                      <tr>
                          <td>$Created.Date<br />$Created.Time12</td>
                          <td>$Message.RAW</td>
                          <td class="text-center"><a href="$Top.join_links($Top.Link,notification,v,$ID)" title="Dismiss"><i class="fas fa-check text-success"></i></a></td>
                      </tr>
                    <% end_loop %>
                </tbody>
            </table>
          </div>
          <% include Pagination Collection=$ActiveNotifications %>
        <% else %>
          You have no notifications
        <% end_if %>
      </div>
    </div>
    
    <% if $ViewedNotifications.Count %>
    <div class="card">
      <div class="card-header p-4">
        <h2 class="m-0">Viewed Notifications</h2>
      </div>
      <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-bordered first">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Message</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <% loop $ViewedNotifications %>
                      <tr>
                          <td>$Created.Date<br />$Created.Time12</td>
                          <td>$Message.RAW</td>
                          <td class="text-center"><a href="$Top.Link(notification)/d/$ID/" title="Archive"><i class="fas fa-times text-danger"></i></a></td>
                      </tr>
                    <% end_loop %>
                </tbody>
            </table>
          </div>
          <% include Pagination Collection=$ViewedNotifications %>
      </div>
    </div>
    <% end_if %>
          
      
  </div>
</div>
