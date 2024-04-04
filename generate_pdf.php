<?php

require 'admin/inc/db_config.php';
require 'admin/inc/essentials.php';
require 'admin/inc/vendor/autoload.php';

session_start();

if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
    redirect('index.php');
}

if (isset($_GET['gen_pdf']) && isset($_GET['id'])) {
    $frm_data = filteration($_GET);

    $query = "SELECT bo.*, bd.*,uc.email FROM `booking_order` bo
      INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
      INNER JOIN `user_cred` uc ON bo.user_id = uc.id
      WHERE ((bo.booking_status='booked' AND bo.arrival=1) 
      OR (bo.booking_status='cancelled' AND bo.refund=1) 
      OR (bo.booking_status='payment failed'))
      AND bo.booking_id ='$frm_data[id]'";

    $res = mysqli_query($con, $query);
    $total_rows = mysqli_num_rows($res);

    if ($total_rows == 0) {
        header('location: index.php');
        exit;
    }

    $data = mysqli_fetch_assoc($res);

    $date = date('h:ia | d-m-Y', strtotime($data['datentime']));
    $checkin = date('d-m-Y', strtotime($data['check_in']));
    $checkout = date('d-m-Y', strtotime($data['check_out']));

    $table_data = "
    <style>
    .container {
        text-align: center;
        margin-bottom: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-left: 150px; 
  }
  
    table, th, td {
      border: 1px solid #ccc;
    }

    th, td {
      padding: 10px;
      text-align: left;
    }

    th {
      background-color: #f2f2f2;
    }

    tr:nth-child(even) {
      background-color: #f2f2f2;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .hotel-heading {
      font-size: 24px;
      font-weight: bold;
    }
    </style>
    
    <h2>BOOKING RECEIPT</h2>
    <div class='container'>
        <h2 class='hotel-heading'>Hotel Khodal - Kagavad</h2>
    </div>
    <table>
      <tr>
        <td><strong>Order ID:</strong> $data[order_id] </td>
        <td><strong>Booking Date:</strong> $date </td> 
      </tr>
      <tr>
        <td colspan='2'><strong>Status:</strong> $data[booking_status]</td> 
      </tr>
      <tr>
        <td><strong>Name:</strong> $data[user_name] </td>
        <td><strong>Email:</strong> $data[email] </td> 
      </tr>
      <tr>
        <td><strong>Phone Number:</strong> $data[phonenum] </td>
        <td><strong>Address:</strong> $data[address] </td> 
      </tr>
      <tr>
        <td><strong>Room Name:</strong> $data[room_name] </td>
        <td><strong>Cost:</strong> INR$data[price] per night </td> 
      </tr>
      <tr>
        <td><strong>Check-in:</strong> $checkin</td>
        <td><strong>Check-out:</strong> $checkout</td> 
      </tr>";

    if ($data['booking_status'] == 'cancelled') {
        $refund = ($data['refund']) ? 'Amount Refunded' : 'Not Yet Refunded';

        $table_data .= "<tr>
            <td><strong>Amount Paid:</strong> $data[trans_amt]</td>
            <td><strong>Refund:</strong> $refund</td> 
        </tr>";
    } elseif ($data['booking_status'] == 'payment failed') {
        $table_data .= "<tr>
            <td><strong>Transaction Amount:</strong> $data[trans_amt]</td>
            <td><strong>Failure Response:</strong> $data[trans_msg]</td> 
        </tr>";
    } else {
        $table_data .= "<tr>
            <td><strong>Room Number:</strong> $data[room_no]</td>
            <td><strong>Amount Paid:</strong> $data[trans_amt]</td> 
        </tr>";
    }

    $table_data .= '</table>'
    ;

    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf();
    $html2pdf->writeHTML($table_data);
    $html2pdf->output();
} else {
    header('location: index.php');
}
