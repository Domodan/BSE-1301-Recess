<?php
require_once('functions.php');

//checks the page you want to visit
//remember 'GET' picks the url of the page you want
if (empty($_SESSION['member_id'])) {
  $page = 'log-in';
}
elseif (empty($_GET['page'])) {
    $page = 'contributions';
}
else {
    $page = $_GET['page']; //gets the exact page you want
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../favicon.ico">

    <title>Recess Family SACCO</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="navbar-fixed-top.css" rel="stylesheet">
  </head>

  <body>

    <!-- Fixed navigation bars
    displays those menu items like contribution, loan request and the rest
     -->
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <!-- this is where our menu begins -->

          <a class="navbar-brand" href="#">Family SACCO</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav">

          <!-- this our clickable menus-->
            <?php if (isset($_SESSION['is_admin'])): ?>
            <li><a href="index.php?page=contributions">Contributions</a></li>
            <li><a href="index.php?page=loan-requests">Loan Requests</a></li>
            <li><a href="index.php?page=business-ideas-from-file">Business Ideas</a></li>
            <li><a href="index.php?page=add-member">Add member</a></li>
            <li><a href="index.php?page=add-investment">Add investment</a></li>
            <?php endif; ?>

            <?php if (isset($_SESSION['is_admin']) || isset($_SESSION['member_id'])): ?>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Reports <span class="caret"></span></a>
              <ul class="dropdown-menu">
                <li><a href="index.php?page=regular-members">Regular Members</a></li>
                <li><a href="index.php?page=amount-in-cash">Amount in Cash</a></li>
                <li><a href="index.php?page=amount-in-loan">Amount in Loan</a></li>
                <li><a href="index.php?page=business-ideas-from-db">Business Ideas</a></li>
                <li><a href="index.php?page=benefits">Benefits</a></li>

                <?php if (isset($_SESSION['is_admin'])): ?>
                <li class="divider"></li>
                <li><a href="index.php?page=total-amount-in-cash">Total amount in cash</a></li>
                <li><a href="index.php?page=total-amount-in-loans">Total amount in loans</a></li>
                <?php endif; ?>
              </ul>
            </li>
            <?php endif; ?>

            <?php if (isset($_SESSION['is_admin']) || isset($_SESSION['member_id'])): ?>
            <li><a href="index.php?page=log-out">Log Out</a></li>
            <?php else: ?>
            <li><a href="index.php?page=log-in">Log In</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav><!-- navigation bar/menu ends here -->

    <div class="container">

<!-- contribution page -->

<?php if ($page == 'contributions') : ?>

      <div class="row">
        <div class="col-lg-12">
          <h3 class="page-header">Contributions</h3>

          <?php
          if (isset($_GET['action'])) {
            switch ($_GET['action']) {
            case 'accept':
              accept_contribution($_GET['contribution_id']);
              break;
            case 'deny':
              deny_contribution($_GET['contribution_id']);
              break;
            default:
              # do nothing...
              break;
            }
          }
          ?>

          <?php $contributions = get_contributions(); ?>

          <!-- checks if contribution had been submitted by the member -->
          <?php if (count($contributions) == 0): ?>
            <div class="alert alert-info">
              <p>No contributions made so far.</p>
            </div>
          <?php else: ?>

          <!-- outputs the table containing contribution made -->
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Amount (UGX)</th>
                  <th>Date</th>
                  <th>Receipt Number</th>
                  <th colspan=2>&nbsp;</th>
                </tr>
              </thead>
              <tbody>
                <?php

                //picks the data inform of array from contribution and fills it in the table
                $i = 0;
                foreach ($contributions as $contribution) {
                  echo "<tr>" .
                        "<td>{$contribution['name']}</td>" .
                        "<td>{$contribution[1]}</td>" .
                        "<td>{$contribution[2]}</td>" .
                        "<td>{$contribution[3]}</td>" .
                        "<td><a href='index.php?page=contributions&action=accept&contribution_id={$contribution['id']}'>Accept</a></td>" .
                        "<td><a href='index.php?page=contributions&action=deny&contribution_id={$contribution['id']}'>Deny</a></td>" .
                        "</tr>";
                  ++$i;
                }
                ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
      <!-- if the get request matches the loan request, the loan page will be displayed -->
<?php elseif ($page == 'loan-requests'): ?>

      <div class="row">
        <div class="col-lg-8">
          <h3 class="page-header">Loan Requests</h3>

          <?php
          if (isset($_GET['action'])) {
            switch ($_GET['action']) {
            case 'accept':
              if (accept_loan_request($_GET['loan_request_id'])) {
                $success_message = "Loan request accepted.";
              }
              else {
                $error_message = "Loan request denied because its amount is more than 1/2 " .
                                  "of the total contributions for this member";
              }
              break;
            case 'deny':
              deny_loan_request($_GET['loan_request_id']);
              break;
            default:
              # do nothing...
              break;
            }
          }
          ?>
<!-- Displays the success message if the request is accepted, else error message 
    is displayed -->
          <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
              <p><?php echo $success_message; ?></p>
            </div>
          <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger">
              <p><?php echo $error_message; ?></p>
            </div>
          <?php endif; ?>

          <?php $loan_requests = get_loan_requests(); ?>

          <!-- checks if the loan request was made -->

          <?php if (count($loan_requests) == 0): ?>
            <div class="alert alert-info">
              <p>No loan requests made so far.</p>
            </div>

          <?php else: ?>
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Name</th>
                <th>Amount (UGX)</th>
               <th colspan=2>&nbsp;</th>
              </tr>
            </thead>
            <tbody>
              <?php
              foreach ($loan_requests as $loan_request) {
                echo "<tr>" .
                      "<td>{$loan_request['name']}</td>" .
                      "<td>{$loan_request[2]}</td>" .
                      "<td><a href='index.php?page=loan-requests&action=accept&loan_request_id={$loan_request['id']}'>Accept</a></td>" .
                      "<td><a href='index.php?page=loan-requests&action=deny&loan_request_id={$loan_request['id']}'>Deny</a></td>" .
                      "</tr>";
              }
              ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
       <!-- if the get request matches benfit, the benefit page will be displayed -->
<?php elseif ($page == 'benefits'): ?>

	  <div class="row">
        <div class="col-lg-8">
          <h3 class="page-header">Benefits</h3>
          <p>Your total benefits: <?php echo get_benefits(); ?> UGX</p>
        </div>
      </div>
       <!-- if the get request matches that for adding new members, the add_member page will be displayed -->
<?php elseif ($page == 'add-member'): ?>

      <div class="row">
        <div class="col-lg-5">

          <?php

          //checks if the form data had been submitted
          if ($_SERVER['REQUEST_METHOD'] == 'POST') {

          //if posted, store results in the following variables
            $name = $_POST['fullname'];
            $initial_contribution = $_POST['initial_contribution'];
            $username = $_POST['username'];
            $password = $_POST['password1'];
            $confirmed_password = $_POST['password2'];

            //compares the two passwords submitted
            if ($password != $confirmed_password) {
              $error_message = "The two passwords do not match.";
            }
            else {
              if (add_member($name, $initial_contribution, $username, $password)) {
                $success_message = "New member successfully added.";
              }
              else {
                $error_message = "Initial contribution too low.";
              }
            }
          }
          ?>

          <h3 class="page-header">Add member</h3>

          <!-- if there was error, display it on top of the form -->
          <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
              <p><?php echo $error_message; ?></p>
            </div>

            <!-- if there was success, display it on top of the form -->
          <?php elseif (isset($success_message)) : ?>
            <div class="alert alert-success">
              <p><?php echo $success_message; ?></p>
            </div>
          <?php endif; ?>

          <form action="" method="post" role="form">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Initial contribution</label>
              <input type="number" name="initial_contribution" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password1" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Confirm Password</label>
              <input type="password" name="password2" class="form-control" required>
            </div>
            <div class="form-group">
              <input type="submit" value="Submit" class="btn btn-primary">
            </div>
          </form>
        </div>
      </div>

      <!-- if the get request matches that for adding investments, the add_investment page will be displayed -->
<?php elseif ($page == 'add-investment'): ?>

      <div class="row">
        <div class="col-lg-5">
          <h3 class="page-header">Add investment</h3>

          <?php

          //checks if the form data had been submitted
          if ($_SERVER['REQUEST_METHOD'] == 'POST') {

          //if posted, store results in the following variables
            $business_idea = $_POST['business_idea'];
            $capital = $_POST['capital'];
            $member = $_POST['member'];

            add_investment($business_idea, $capital, $member);
            $success_message = "Investment successfully added.";
          }
          ?>

        <!-- if there was success, display it on top of the form -->
          <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
              <p><?php echo $success_message; ?></p>
            </div>
          <?php endif; ?>

          <form action="" method="post" role="form">
            <div class="form-group">
              <label>Business idea</label>
              <input type="text" name="business_idea" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Capital</label>
              <input type="number" name="capital" class="form-control" min="0" required>
            </div>
            <div class="form-group">
              <label>Member</label>
              <input type="text" name="member" class="form-control" required>
            </div>
            <div class="form-group">
              <input type="submit" value="Submit" class="btn btn-primary">
            </div>
          </form>
        </div>
      </div>

      <!-- if the get request matches that for logging in, the log-in page will be displayed -->
<?php elseif ($page == 'log-in'): ?>

	   <div class="row">
        <div class="col-lg-5">
          <h3 class="page-header">Log In</h3>

          <?php
          if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            if (login_successful($username, $password)) {
              // Redirect user to the page showing contributions.
              if (isset($_SESSION['is_admin'])) {
                header('Location: index.php?page=contributions');
              } else {
                header('Location: index.php?page=business-ideas');
              }
              exit();
            }
            else {
              $error_message = "Invalid username/password combination.";
            }
          }
          ?>

          <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
              <p><?php echo $error_message; ?></p>
            </div>
          <?php endif; ?>

          <form action="" method="post" role="form">
            <div class="form-group">
              <label>Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" class="form-control" min="0" required>
            </div>
            <div class="form-group">
              <input type="submit" value="Login" class="btn btn-primary">
            </div>
          </form>
        </div>
      </div>

<?php elseif ($page == 'log-out'): ?>
  <?php
      unset($_SESSION['member_id']);
      if (isset($_SESSION['is_admin'])) {
        unset($_SESSION['is_admin']);
      }
      
      header('Location: index.php?page=log-in');
      exit();
  ?>

      <!-- if the get request matches that for regular members, the regular_members page will be displayed -->
 <?php elseif ($page == 'regular-members'): ?>

      <div class="row">
        <div class="col-lg-8">
          <h3 class="page-header">Regular members</h3>
          <?php $regular_members = get_regular_members(); ?>
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Name</th>
                <th>Since</th>
              </tr>
            </thead>
            <tbody>
            <?php
            foreach ($regular_members as $member) {
              echo "<tr>" .
                    "<td>{$member['name']}</td>" .
                    "<td>" . date_format(new DateTime($member['date_entered']), 'F jS, Y') . "</td>" .
                    "</tr>";
            }
            ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- if the get request matches that for business-ideas, the business-ideas page will be displayed -->
 <?php elseif ($page == 'business-ideas-from-file'): ?>

      <div class="row">
        <div class="col-lg-8">
          <h3 class="page-header">Business Ideas from File</h3>
          
          <?php
            if (isset($_GET['action']) && $_GET['action'] == 'save') {
                add_business_idea($_GET['idea-id']);
            }
          ?>

          <?php $business_ideas = get_business_ideas_from_file(); ?>

          <?php if (count($business_ideas) == 0): ?>
            <div class="alert alert-info">
                <p>No business ideas submitted so far.</p>
            </div>

          <?php else: ?>
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Idea</th>
                    <th>Capital</th>
                    <th>Description</th>
                    <th>&nbsp;</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                foreach ($business_ideas as $business_idea) {
                    echo "<tr>" .
                          "<td>{$business_idea['name']}</td>" .
                          "<td>{$business_idea[1]}</td>" .
                          "<td>{$business_idea[2]}</td>" .
                          "<td>{$business_idea['description']}</td>" .
                          "<td><a href='index.php?page=business-ideas-from-file&action=save&idea-id={$business_idea['id']}'>Save</a></td>" .
                          "</tr>";
                }
                ?>
                </tbody>
              </table>
          <?php endif; ?>
        </div>
      </div>
      
 <?php elseif ($page == 'business-ideas-from-db'): ?>

      <div class="row">
        <div class="col-lg-8">
          <h3 class="page-header">Business Ideas</h3>

          <!-- call the function get_business_ideas() -->
          <?php $business_ideas = get_business_ideas_from_db(); ?>

           <!-- checks if business_ideas had been submitted by the adminitrator -->
          <?php if (count($business_ideas) == 0): ?>
            <div class="alert alert-info">
                <p>No business to show.</p>
            </div>

          <!--outputs the results of business_ideas -->
          <?php else: ?>
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Idea</th>
                    <th>Capital</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                foreach ($business_ideas as $business_idea) {
                    echo "<tr>" .
                          "<td>{$business_idea['name']}</td>" .
                          "<td>{$business_idea['idea']}</td>" .
                          "<td>{$business_idea['capital']}</td>" .
                          "<td>{$business_idea['description']}</td>" .
                          "</tr>";
                }
                ?>
                </tbody>
              </table>
          <?php endif; ?>
        </div>
      </div>

 <?php elseif ($page == 'amount-in-cash'): ?>

      <div class="row">
        <div class="col-lg-8">
          <h3 class="page-header">Amount in Cash</h3>
          <p>Your current balance is: <?php echo get_amount_in_cash($_SESSION['member_id']); ?> UGX</p>
        </div>
      </div>

 <?php elseif ($page == 'amount-in-loan'): ?>

      <div class="row">
        <div class="col-lg-8">
          <h3 class="page-header">Amount in Loan</h3>

          <?php $amount_in_loan = get_amount_in_loan($_SESSION['member_id']); ?>
          <p>
            Your current balance is: <?php echo $amount_in_loan; ?> UGX
          </p>

          <p>
            <?php if ($amount_in_loan > 0): ?>
              <a href="index.php?page=pay-loan" class="btn btn-xs btn-default">Pay loan</a>
            <?php endif; ?>
          </p>
        </div>
      </div>

 <?php elseif ($page == 'pay-loan'): ?>

      <div class="row">
        <div class="col-lg-5">

          <?php
          if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $amount = $_POST['amount'];
            if ($amount > get_amount_in_cash($_SESSION['member_id'])) {
              $error_message = "Your account balance is to low to complete this transaction.";
            }
            else {
              pay_loan($amount);
              header('Location: index.php?page=amount-in-loan');
              exit();
            }
          }
          ?>

          <h3 class="page-header">Pay your loan</h3>
          <p>
            Your current cash balance is: <?php echo get_amount_in_cash($_SESSION['member_id']); ?> UGX
          </p>
          <p>
            Your current loan balance is: <?php echo get_amount_in_loan($_SESSION['member_id']); ?> UGX
          </p>

          <form action='' method='post' role='form'>
              <div class="form-group">
                <label>Amount</label>
                <input type="number" name="amount" class="form-control" required>
              </div>

              <input type="submit" value='Pay' class="btn btn-sm btn-primary">
          </form>
        </div>
      </div>

 <?php elseif ($page == 'total-amount-in-cash'): ?>

      <div class="row">
        <div class="col-lg-8">
          <h3 class="page-header">Total amount in cash</h3>
          <p>Current balance is: <?php echo get_total_amount_in_cash(); ?> UGX</p>
        </div>
      </div>

 <?php elseif ($page == 'total-amount-in-loans'): ?>

      <div class="row">
        <div class="col-lg-8">
          <h3 class="page-header">Total amount in loans</h3>
          <p>Current balance is: <?php echo get_total_amount_in_loans(); ?> UGX</p>
        </div>
      </div>

<?php endif; ?>

    </div> <!-- /container -->


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="js/jquery-3.1.0.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
