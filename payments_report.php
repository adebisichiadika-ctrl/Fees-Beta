<?php
    include 'db_connect.php';
    $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
    
    // Calculate Total Outstanding Fees (all students)
    $outstanding_query = $conn->query("
        SELECT 
            COALESCE(SUM(ef.total_fee), 0) as total_fees,
            COALESCE((SELECT SUM(amount) FROM payments), 0) as total_paid
        FROM student_ef_list ef
    ");
    $outstanding_data = $outstanding_query->fetch_assoc();
    $total_all_fees = $outstanding_data['total_fees'];
    $total_all_paid = $outstanding_data['total_paid'];
    $total_outstanding = $total_all_fees - $total_all_paid;
    
    // Calculate Outstanding Fees for the selected month (students who have balances)
    $monthly_outstanding_query = $conn->query("
        SELECT 
            ef.id,
            ef.total_fee,
            COALESCE((SELECT SUM(amount) FROM payments WHERE ef_id = ef.id), 0) as paid
        FROM student_ef_list ef
    ");
    $students_with_balance = 0;
    while($row = $monthly_outstanding_query->fetch_assoc()){
        if(($row['total_fee'] - $row['paid']) > 0){
            $students_with_balance++;
        }
    }
    
    // Get total students enrolled
    $total_students = $conn->query("SELECT COUNT(*) as cnt FROM student_ef_list")->fetch_assoc()['cnt'];
?>
<style>
    .summary-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        padding: 20px;
        color: white;
        margin-bottom: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .summary-card.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .summary-card.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .summary-card.info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    .summary-card h3 {
        margin: 0;
        font-size: 28px;
        font-weight: bold;
    }
    .summary-card p {
        margin: 5px 0 0 0;
        opacity: 0.9;
        font-size: 14px;
    }
    .summary-card .icon {
        font-size: 40px;
        opacity: 0.3;
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
    }
    .summary-card-wrapper {
        position: relative;
    }
</style>
<div class="container-fluid">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <b><i class="fa fa-chart-bar"></i> Payments Report</b>
            </div>
            <div class="card-body">
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="summary-card success summary-card-wrapper">
                            <span class="icon"><i class="fa fa-money-bill-wave"></i></span>
                            <h3><?php echo number_format($total_all_fees, 2) ?></h3>
                            <p>Total Fees (All Students)</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card info summary-card-wrapper">
                            <span class="icon"><i class="fa fa-check-circle"></i></span>
                            <h3><?php echo number_format($total_all_paid, 2) ?></h3>
                            <p>Total Collected</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card warning summary-card-wrapper">
                            <span class="icon"><i class="fa fa-exclamation-triangle"></i></span>
                            <h3><?php echo number_format($total_outstanding, 2) ?></h3>
                            <p>Total Outstanding</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card summary-card-wrapper">
                            <span class="icon"><i class="fa fa-users"></i></span>
                            <h3><?php echo $students_with_balance ?> / <?php echo $total_students ?></h3>
                            <p>Students with Balance</p>
                        </div>
                    </div>
                </div>
                
                <!-- Month Filter -->
                <div class="row justify-content-center mb-3">
                    <label for="" class="mt-2 mr-2"><b>Filter by Month:</b></label>
                    <div class="col-sm-3">
                        <input type="month" name="month" id="month" value="<?php echo $month ?>" class="form-control">
                    </div>
                </div>
                <hr>
                
                <!-- Payments Table -->
                <div class="col-md-12">
                    <h5 class="mb-3"><i class="fa fa-list"></i> Payment Transactions for <?php echo date("F Y", strtotime($month.'-01')) ?></h5>
                    <table class="table table-bordered table-striped" id='report-list'>
                        <thead class="thead-dark">
                            <tr>
                                <th class="text-center">#</th>
                                <th class="">Date</th>
                                <th class="">ID No.</th>
                                <th class="">EF No.</th>
                                <th class="">Name</th>
                                <th class="text-right">Paid Amount</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            $total = 0;
                            $payments = $conn->query("SELECT p.*,s.name as sname, ef.ef_no,s.id_no FROM payments p inner join student_ef_list ef on ef.id = p.ef_id inner join student s on s.id = ef.student_id where date_format(p.date_created,'%Y-%m') = '$month' order by unix_timestamp(p.date_created) asc ");
                            if($payments->num_rows > 0):
                            while($row = $payments->fetch_array()):
                                $total += $row['amount'];
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $i++ ?></td>
                                <td>
                                    <p><b><?php echo date("M d, Y H:i A", strtotime($row['date_created'])) ?></b></p>
                                </td>
                                <td>
                                    <p><b><?php echo $row['id_no'] ?></b></p>
                                </td>
                                <td>
                                    <p><b><?php echo $row['ef_no'] ?></b></p>
                                </td>
                                <td>
                                    <p><b><?php echo ucwords($row['sname']) ?></b></p>
                                </td>
                                <td class="text-right">
                                    <p><b><?php echo number_format($row['amount'], 2) ?></b></p>
                                </td>
                                <td>
                                    <p><?php echo $row['remarks'] ?></p>
                                </td>
                            </tr>
                            <?php 
                            endwhile;
                            else:
                            ?>
                            <tr>
                                <td class="text-center" colspan="7"><em>No payment transactions for this month.</em></td>
                            </tr>
                            <?php 
                            endif;
                            ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="5" class="text-right">Total Payments (This Month):</th>
                                <th class="text-right"><?php echo number_format($total, 2) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <!-- Outstanding Fees Breakdown -->
                <hr>
                <div class="col-md-12">
                    <h5 class="mb-3"><i class="fa fa-exclamation-circle text-warning"></i> Outstanding Fees Breakdown</h5>
                    <table class="table table-bordered table-hover" id="outstanding-list">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th>ID No.</th>
                                <th>EF No.</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th class="text-right">Total Fee</th>
                                <th class="text-right">Paid</th>
                                <th class="text-right">Outstanding</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $j = 1;
                            $grand_total_fee = 0;
                            $grand_total_paid = 0;
                            $grand_outstanding = 0;
                            
                            $outstanding_fees = $conn->query("
                                SELECT 
                                    ef.*,
                                    s.name as sname,
                                    s.id_no,
                                    CONCAT(c.course, ' - ', c.level) as course_name,
                                    COALESCE((SELECT SUM(amount) FROM payments WHERE ef_id = ef.id), 0) as total_paid
                                FROM student_ef_list ef 
                                INNER JOIN student s ON s.id = ef.student_id 
                                INNER JOIN courses c ON c.id = ef.course_id
                                ORDER BY (ef.total_fee - COALESCE((SELECT SUM(amount) FROM payments WHERE ef_id = ef.id), 0)) DESC
                            ");
                            
                            if($outstanding_fees->num_rows > 0):
                            while($row = $outstanding_fees->fetch_assoc()):
                                $balance = $row['total_fee'] - $row['total_paid'];
                                $grand_total_fee += $row['total_fee'];
                                $grand_total_paid += $row['total_paid'];
                                $grand_outstanding += $balance;
                                
                                $status_class = $balance <= 0 ? 'success' : ($balance < $row['total_fee'] * 0.5 ? 'warning' : 'danger');
                                $status_text = $balance <= 0 ? 'Fully Paid' : ($balance < $row['total_fee'] * 0.5 ? 'Partial' : 'Pending');
                            ?>
                            <tr class="<?php echo $balance > 0 ? '' : 'table-success' ?>">
                                <td class="text-center"><?php echo $j++ ?></td>
                                <td><b><?php echo $row['id_no'] ?></b></td>
                                <td><b><?php echo $row['ef_no'] ?></b></td>
                                <td><b><?php echo ucwords($row['sname']) ?></b></td>
                                <td><?php echo $row['course_name'] ?></td>
                                <td class="text-right"><?php echo number_format($row['total_fee'], 2) ?></td>
                                <td class="text-right text-success"><?php echo number_format($row['total_paid'], 2) ?></td>
                                <td class="text-right <?php echo $balance > 0 ? 'text-danger font-weight-bold' : 'text-success' ?>">
                                    <?php echo number_format($balance, 2) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-<?php echo $status_class ?>"><?php echo $status_text ?></span>
                                </td>
                            </tr>
                            <?php 
                            endwhile;
                            else:
                            ?>
                            <tr>
                                <td class="text-center" colspan="9"><em>No enrollment records found.</em></td>
                            </tr>
                            <?php 
                            endif;
                            ?>
                        </tbody>
                        <tfoot class="bg-secondary text-white">
                            <tr>
                                <th colspan="5" class="text-right">Grand Totals:</th>
                                <th class="text-right"><?php echo number_format($grand_total_fee, 2) ?></th>
                                <th class="text-right"><?php echo number_format($grand_total_paid, 2) ?></th>
                                <th class="text-right"><?php echo number_format($grand_outstanding, 2) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <hr>
                <div class="col-md-12 mb-4">
                    <center>
                        <button class="btn btn-success btn-sm col-sm-2 mr-2" type="button" id="print"><i class="fa fa-print"></i> Print Payments</button>
                        <button class="btn btn-info btn-sm col-sm-2" type="button" id="print-outstanding"><i class="fa fa-print"></i> Print Outstanding</button>
                    </center>
                </div>
            </div>
        </div>
    </div>
</div>
<noscript id="print-styles">
    <style>
        table#report-list, table#outstanding-list {
            width: 100%;
            border-collapse: collapse;
        }
        table#report-list td, table#report-list th,
        table#outstanding-list td, table#outstanding-list th {
            border: 1px solid;
            padding: 5px;
        }
        p {
            margin: unset;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .summary-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 10px;
            display: inline-block;
            width: 23%;
            text-align: center;
        }
    </style>
</noscript>
<script>
$('#month').change(function(){
    location.replace('index.php?page=payments_report&month='+$(this).val())
})

$('#print').click(function(){
    var _c = $('#report-list').clone();
    var ns = $('noscript#print-styles').clone();
    ns.append(_c);
    var nw = window.open('', '_blank', 'width=900,height=600');
    nw.document.write('<h3 class="text-center">Payment Report - <?php echo date("F Y", strtotime($month.'-01')) ?></h3>');
    nw.document.write('<p><b>Total Fees:</b> <?php echo number_format($total_all_fees, 2) ?> | <b>Total Collected:</b> <?php echo number_format($total_all_paid, 2) ?> | <b>Outstanding:</b> <?php echo number_format($total_outstanding, 2) ?></p>');
    nw.document.write('<hr>');
    nw.document.write(ns.html());
    nw.document.close();
    nw.print();
    setTimeout(() => {
        nw.close();
    }, 500);
})

$('#print-outstanding').click(function(){
    var _c = $('#outstanding-list').clone();
    var ns = $('noscript#print-styles').clone();
    ns.append(_c);
    var nw = window.open('', '_blank', 'width=1000,height=600');
    nw.document.write('<h3 class="text-center">Outstanding Fees Report</h3>');
    nw.document.write('<p><b>Generated:</b> <?php echo date("F d, Y") ?> | <b>Total Outstanding:</b> <?php echo number_format($total_outstanding, 2) ?></p>');
    nw.document.write('<hr>');
    nw.document.write(ns.html());
    nw.document.close();
    nw.print();
    setTimeout(() => {
        nw.close();
    }, 500);
})
</script>