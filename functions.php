<?php
session_start();
$link = mysqli_connect('localhost', 'root', '', 'sacco');
$data_from_file = file('family_sacco.txt');

function add_business_idea($idea_id) {
  global $link;
  global $data_from_file;

  // Save idea to the database.
  $idea = explode(' ', $data_from_file[$idea_id]);
  
  $member_id = $idea[count($idea) - 1];
  $amount = $idea[2];
  $description = $idea[3];
  if ((count($idea) - 2) != 3) {
    for ($i = 4; $i <= (count($idea) - 2); ++$i) {
        $description .= " {$idea[$i]}";
    }
  }
  
  if ($amount <= (get_total_amount_in_cash() / 2)) {
    $sql = sprintf("INSERT INTO ideas (member_id, name, description, capital)
                    VALUES (%d, '%s', '%s', %d)",
                    $member_id, $idea[1], $description, $amount);
  }
  mysqli_query($link, $sql);

  // Remove the idea from the array $data_from_file.
  unset($data_from_file[$idea_id]);

  // Update the data in the file.
  file_put_contents('family_sacco.txt', $data_from_file);   
}

function add_member($name, $username, $password) {
    global $link;

    $sql = sprintf("INSERT INTO members (name, username, password)
                    VALUES ('%s', '%s', SHA1('%s'))",
                    $name, $username, $password);
    mysqli_query($link, $sql);

}

function get_maximum_contribution() {
  global $link;

  // Get all members.
  $sql = sprintf("SELECT id FROM members");
  $results = mysqli_query($link, $sql);

  $member_ids = array();
  while ($row = mysqli_fetch_array($results)) {
    $member_ids[] = $row['id'];
  }

  // For each member, get their total contribution.
  $total_contributions_per_member = array();
  foreach ($member_ids as $id) {
    $total_contributions_per_member[] = get_amount_in_cash($id);
  }

  // Return the maximum.
  return max($total_contributions_per_member);
}

function add_investment($business_idea, $capital, $member) {
    global $link;

    $sql = sprintf("INSERT INTO investments (business_idea, capital, member)
                    VALUES ('%s', %d, '%s')",
                    $business_idea, $capital, $member);
    mysqli_query($link, $sql);
}

function get_contributions() {
    global $link;
    global $data_from_file;

    $contributions = array();
    $id = 0; // The position of a record in the $data_from_file array.
    foreach ($data_from_file as $data) {
        $data = explode(' ', $data);
        if ($data[0] == 'contribution') {
          $data['id'] = $id;

          // Get the name of the member.
          $data['name'] = get_member_name($data[4]);

          // Add it to the list of contributions to be returned.
          $contributions[] = $data;
        }

        ++$id;
    }

    return $contributions;
}

function accept_contribution($contribution_id) {
  global $link;
  global $data_from_file;

  // Save contribution to the database.
  $contribution = explode(' ', $data_from_file[$contribution_id]);
  $sql = sprintf("INSERT INTO contributions (member_id, amount, receipt_number)
                  VALUES (%d, %d, %d)",
                  $contribution[4], $contribution[1], $contribution[3]);
  mysqli_query($link, $sql);

  // Remove the contribution from the array $data_from_file.
  unset($data_from_file[$contribution_id]);

  // Update the data in the file.
  file_put_contents('family_sacco.txt', $data_from_file);
}

function deny_contribution($contribution_id) {
  global $data_from_file;

  // Remove the contribution from the array $data_from_file.
  unset($data_from_file[$contribution_id]);

  // Update the data in the file.
  file_put_contents('family_sacco.txt', $data_from_file);
}

function get_loan_requests() {
    global $link;
    global $data_from_file;

    $loan_requests = array();
    $id = 0; // The position of a record in the $data_from_file array.
    foreach ($data_from_file as $data) {
        $data = explode(' ', $data);
        if ($data[0] == 'loan' && $data[1] == 'request') {
          $data['id'] = $id;

          // Get the name of the member.
          $data['name'] = get_member_name($data[3]);

          // Add it to the list of loan requests to be returned.
          $loan_requests[] = $data;
        }

        ++$id;
    }

    return $loan_requests;
}

function accept_loan_request($loan_request_id) {
  global $link;
  global $data_from_file;

  // Save request to the database.
  $loan_request = explode(' ', $data_from_file[$loan_request_id]);
  $amount = $loan_request[2];
  $sql = sprintf("INSERT INTO loans (member_id, amount, balance)
                  VALUES (%d, %d, %d)",
                  $loan_request[3], $amount, $amount + (0.03 * $amount));
  mysqli_query($link, $sql);

  // Remove the loan request from the array $data_from_file.
  unset($data_from_file[$loan_request_id]);

  // Update the data in the file.
  file_put_contents('family_sacco.txt', $data_from_file);
}

function deny_loan_request($loan_request_id) {
  global $link;
  global $data_from_file;

  // Save request to the database.
  $loan_request = explode(' ', $data_from_file[$loan_request_id]);
  $sql = sprintf("INSERT INTO loans (member_id, amount, balance, status)
                  VALUES (%d, %d, %d, 'denied')",
                  $loan_request[3], $loan_request[2], $loan_request[2]);
  mysqli_query($link, $sql);

  // Remove the contribution from the array $data_from_file.
  unset($data_from_file[$loan_request_id]);

  // Update the data in the file.
  file_put_contents('family_sacco.txt', $data_from_file);
}

function get_business_ideas_from_file() {
    global $link;
    global $data_from_file;

    $business_ideas = array();
    $id = 0; // The position of a record in the $data_from_file array.
    foreach ($data_from_file as $data) {
        $data = explode(' ', $data);
        if ($data[0] == 'idea') {          
          // Get the name of the member.
          $data['name'] = get_member_name($data[count($data) - 1]);
          
          $description = $data[3];
          if ((count($data) - 3) != 3) {
            for ($i = 4; $i <= (count($data) - 3); ++$i) {
                $description .= " {$data[$i]}";
            }
          }
          
          $data['description'] = $description;
          $data['id'] = $id;

          // Add it to the list of business ideas to be returned.
          $business_ideas[] = $data;
        }
        
        ++$id;
    }

    return $business_ideas;
}

function get_business_ideas_from_db() {
    global $link;
    
    $sql = sprintf("SELECT * FROM ideas WHERE status='pending'");
    $results = mysqli_query($link, $sql);

    $business_ideas = array();
    while ($row = mysqli_fetch_array($results)) {
        $business_ideas[] = $row;
    }
    
    return $business_ideas;
}

function get_amount_in_cash($member_id) {
    global $link;

    $sql = sprintf("SELECT SUM(amount) AS total_contributions FROM contributions WHERE member_id = %d",
                    $member_id);
    $result = mysqli_query($link, $sql);

    $row = mysqli_fetch_array($result);
    $total_contributions = $row['total_contributions'];
    if ($total_contributions == NULL) {
        $total_contributions = 0;
    }

    return ($total_contributions - get_amount_in_loan($member_id));
}

function get_amount_in_loan($member_id) {
    global $link;

    $sql = sprintf("SELECT SUM(balance) AS total_loans FROM loans
                    WHERE member_id = %d AND status = 'approved'",
                    $member_id);
    $result = mysqli_query($link, $sql);

    $row = mysqli_fetch_array($result);
    $total_amount_in_loans = $row['total_loans'];
    if ($total_amount_in_loans == NULL) {
        $total_amount_in_loans = 0;
    }

    return $total_amount_in_loans;
}

function get_total_amount_in_cash() {
    global $link;

    $sql = sprintf("SELECT SUM(amount) AS total_contributions FROM contributions");
    $result = mysqli_query($link, $sql);

    $row = mysqli_fetch_array($result);
    $total_contributions = $row['total_contributions'];
    if ($total_contributions == NULL) {
        $total_contributions = 0;
    }

    return ($total_contributions - get_total_amount_in_loans());
}

function get_total_amount_in_loans() {
    global $link;

    $sql = sprintf("SELECT SUM(balance) AS total_loans FROM loans");
    $result = mysqli_query($link, $sql);

    $row = mysqli_fetch_array($result);
    $total_amount_in_loans = $row['total_loans'];
    if ($total_amount_in_loans == NULL) {
        $total_amount_in_loans = 0;
    }

    return $total_amount_in_loans;
}

function get_regular_members() {
    global $link;

    $sql = sprintf("SELECT name, date_entered FROM members");
    $results = mysqli_query($link, $sql);

    $regular_members = array();
    while ($row = mysqli_fetch_array($results)) {
      $regular_members[] = $row;
    }

    return $regular_members;
}

function get_benefits() {
    global $link;

    $sql = sprintf("SELECT SUM(amount) AS total_benefits FROM benefits WHERE member_id = %d",
                    $_SESSION['member_id']);
    $result = mysqli_query($link, $sql);

    $row = mysqli_fetch_array($result);
    $total_benefits = $row['total_benefits'];
    if ($total_benefits == NULL) {
        $total_benefits = 0;
    }

    return $total_benefits;
}

function login_successful($username, $password) {
    global $link;

    $sql = sprintf("SELECT id, is_admin FROM members WHERE username = '%s' AND password = SHA1('%s')",
                    $username, $password);
    $result = mysqli_query($link, $sql);
    if (mysqli_num_rows($result) == 0) {
      return false;
    }
    else {
      $row = mysqli_fetch_array($result);
      if ($row['is_admin']) {
        $_SESSION['is_admin'] = TRUE;
      }

      $_SESSION['member_id'] = $row['id'];

      return true;
    }
}

function get_member_name($member_id) {
  global $link;

  $sql = sprintf("SELECT name FROM members WHERE id = %d",
                  $member_id);
  $result = mysqli_query($link, $sql);
  $row = mysqli_fetch_array($result);

  return $row['name'];
}

?>
