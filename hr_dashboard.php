<?php
	include 'common/header.php';
	$_SESSION['pageinfo'] = "Dashboard";

	include_once("common_db.inc.php");
	$link_id=db_connect();

	include_once("spp_v4_web/bin-release/WEB_REQ_flow.php");

	$query_empno="select EMPREFNOYN from mas_optset";
	$result_empno=mysqli_query($link_id,$query_empno);
	if(mysqli_num_rows($result_empno)>0)
	{
		$arrempno= mysqli_fetch_array($result_empno);
		$refnoyn=$arrempno[0];
	}

	//Grievance Caption
	$query_captions="select * from web_captions where FORMID=8";
	$result_captions=mysqli_query($link_id,$query_captions);
	$res_captions=mysqli_fetch_array($result_captions);
	$gricaption=$res_captions['USAGE_CAPTION'];
	
	//Display Employee List
	$emplist="";
	$query_memonleave="SELECT EMP.EMPID, EMP.REFNO, EMPNAME, ED.DESIGNATION, IF(ACTIVEYN=1,'success','danger') AS STATUS FROM mas_employee EMP INNER JOIN mas_employeedet ED ON ED.EMPID = EMP.EMPID INNER JOIN mas_users MU ON MU.EMPID = EMP.EMPID WHERE ED.EFFTODATE IS NULL ORDER BY ACTIVEYN DESC, EMPID";
	$result_memonleave=mysqli_query($link_id,$query_memonleave);
	$inputcounter=0;
	if(mysqli_num_rows($result_memonleave)>0)
	{
		while($arrmemonleave=mysqli_fetch_array($result_memonleave))
		{
			$emplist.='<tr>
								<td align="right">'.$arrmemonleave["EMPID"].'</td>
								<td align="right">'.$arrmemonleave["REFNO"].'</td>
								<td>
									<div class="media-left">
										<div class="media-left"><a class="text-default text-semibold">'.$arrmemonleave["EMPNAME"].'</a></div>
										<div class="text-muted text-size-small" align="left">
											<span class="status-mark border-'.$arrmemonleave["STATUS"].' position-left"></span>
											'.$arrmemonleave["DESIGNATION"].'
										</div>
									</div>
								</td>
								<td>
									<form method="post" action="spp_v4_web/bin-release/employeesummary_ui.php?id='.base64_encode($arrmemonleave["EMPID"]).'">
										<input type="hidden" id="eid" name="eid" value='.$arrmemonleave["EMPID"].'>
										<button type="submit" id="emp" name="emp" style="background:none;border:0"><span class="fa fa-eye" style="color:#1a8cff"></span></button>
									</form>
								</td>
								<td>';
			if ($arrmemonleave["STATUS"] == "success")
				$emplist.='<form method="post" action="spp_v4_web/bin-release/applyempleave_ui.php">
										<input type="hidden" id="eid" name="eid" value='.$arrmemonleave["EMPID"].'>
										<input type="hidden" id="eref" name="eref" value='.$arrmemonleave["REFNO"].'>
										<input type="hidden" id="ename" name="ename" value='.$arrmemonleave["EMPNAME"].'>
										<button type="submit" id="emp" name="emp" style="background:none;border:0"><span class="fa fa-plane" style="color:#1a8cff"></span></button>
									</form>';

			$emplist.='</td></tr>';
		}
	}
	//End of Display Employee List

	//Display Members on Leave Today
	$query_memonleave="SELECT DISTINCT EMP.EMPID, MAX(REFNO) AS REFNO, MAX(EMPNAME) AS EMPNAME, MAX(ED.DESIGNATION) AS DESIGNATION,MAX(ED.DEPARTMENT) AS DEPARTMENT, IF(SUM(FIRSTHALFYN) >= 1 AND SUM(SECONDHALFYN) >= 1,'Full Day',IF(SUM(FIRSTHALFYN) >= 1 AND SUM(SECONDHALFYN) = 0,'First Half','Second Half')) AS LEVSTATUS FROM mas_employee EMP INNER JOIN mas_employeedet ED ON ED.EMPID = EMP.EMPID INNER JOIN lev_details LD ON LD.EMPID = EMP.EMPID WHERE LEAVEDATE = '".date("Y-m-d")."' AND LD.APPROVEDYN = 1 AND ED.EFFTODATE IS NULL GROUP BY EMP.EMPID";
	$result_memonleave=mysqli_query($link_id,$query_memonleave);
	if(mysqli_num_rows($result_memonleave)>0)
	{
		while($arrmemonleave=mysqli_fetch_array($result_memonleave))
		{
			if($refnoyn == 1)
				$empno=$arrmemonleave["REFNO"];
			else
				$empno=$arrmemonleave["EMPID"];

			$memonleavetoday.='<tr>
								<td>
									<div class="media-left">
										<div class="media-left"><a class="text-default text-semibold">'.$arrmemonleave["EMPNAME"].' ('.$empno.')</a></div>
										<div class="text-muted text-size-small" align="left">
											Designation:'.$arrmemonleave["DESIGNATION"].' <br>
											Department: '.$arrmemonleave["DEPARTMENT"].'
										</div>
									</div>
								</td>
								<td align="left">'.$arrmemonleave["LEVSTATUS"].'</td>
							</tr>';
		}
	}
	//End of Display Members on Leave Today

	if (strpos($requisition,"GRID_CHILD")>0)
	{
		$reqstatus='<?xml version="1.0" standalone="yes"?>';
		$reqstatus=$reqstatus.$requisition;		
		$xmlreqstatus = new SimpleXMLElement($reqstatus);
		$rowcnt=count($xmlreqstatus->GRID_CHILD);
	}

	$urllev="";
	$urllevcanc="";
	$urlloan="";
	$urladv="";
	$urlreim="";
	$urlreimbill="";
	$urltds="";
	$urlgriev="";

	$rowcntlev=0;
	$rowcntlevcanc=0;
	$rowcntloan=0;
	$rowcntadv=0;
	$rowcntreim=0;
	$rowcntreimbill=0;
	$rowcnttds=0;
	$rowcntgriev=0;

	$rowcntgen=0;
	
	if ($rowcnt>0)
	{
		$inti=0;
		while ($inti<$rowcnt)
		{
			$rowcntgen=substr($xmlreqstatus->GRID_CHILD[$inti]->STATUS,strpos($xmlreqstatus->GRID_CHILD[$inti]->STATUS,"(")+1,(strpos($xmlreqstatus->GRID_CHILD[$inti]->STATUS,")")-strpos($xmlreqstatus->GRID_CHILD[$inti]->STATUS,"("))-1);

			switch ($xmlreqstatus->GRID_CHILD[$inti]->REQID) {
				case '1':
					$rowcntlev=$rowcntgen;
					$urllev="spp_v4_web/bin-release/LeaveManagement_ui.php";
					$pendingrequisitions.='<tr>
											<td>
												<div class="media-left">
													<div class="media-left"><a onclick="location.href=\''.$urllev.'\'" class="text-semibold">Leave</a></div>
												</div>
											</td>
											<td><h6>'.$rowcntgen.'</h6></td>
										</tr>';
					break;

				case '2':
					$rowcntloan=$rowcntgen;
					$urlloan="spp_v4_web/bin-release/frm_LoanApprove.php";
					$pendingrequisitions.='<tr>
											<td>
												<div class="media-left">
													<div class="media-left"><a onclick="location.href=\''.$urlloan.'\'" class="text-semibold">Loan</a></div>
												</div>
											</td>
											<td><h6>'.$rowcntgen.'</h6></td>
										</tr>';
					break;

				case '3':
					$rowcntadv=$rowcntgen;
					$urladv="spp_v4_web/bin-release/frm_AdvanceApprove.php";
					$pendingrequisitions.='<tr>
											<td>
												<div class="media-left">
													<div class="media-left"><a onclick="location.href=\''.$urladv.'\'" class="text-semibold">Advance</a></div>
												</div>
											</td>
											<td><h6>'.$rowcntgen.'</h6></td>
										</tr>';
					break;

				case '6':
					$rowcnttds=$rowcntgen;
					$urltds="spp_v4_web/bin-release/investmentdetails_ui.php";
					$pendingrequisitions.='<tr>
											<td>
												<div class="media-left">
													<div class="media-left"><a onclick="location.href=\''.$urltds.'\'" class="text-semibold">TDS Investments</a></div>
												</div>
											</td>
											<td><h6>'.$rowcntgen.'</h6></td>
										</tr>';
					break;

				case '7':
					$rowcntlevcanc=$rowcntgen;
					$urllevcanc="spp_v4_web/bin-release/LeaveCancellation_ui.php";
					$pendingrequisitions.='<tr>
											<td>
												<div class="media-left">
													<div class="media-left"><a onclick="location.href=\''.$urllevcanc.'\'" class="text-semibold">Leave Cancellation</a></div>
												</div>
											</td>
											<td><h6>'.$rowcntgen.'</h6></td>
										</tr>';
					break;

				case '8':
					$rowcntreim=$rowcntgen;
					$urlreim="spp_v4_web/bin-release/frmRMB_Approve.php";
					$pendingrequisitions.='<tr>
											<td>
												<div class="media-left">
													<div class="media-left"><a onclick="location.href=\''.$urlreim.'\'" class="text-semibold">Reimbursement</a></div>
												</div>
											</td>
											<td><h6>'.$rowcntgen.'</h6></td>
										</tr>';
					break;

				case '9':
					$rowcntreimbill=$rowcntgen;
					$urlreimbill="spp_v4_web/bin-release/frm_ReimbursementBill.php";
					$pendingrequisitions.='<tr>
											<td>
												<div class="media-left">
													<div class="media-left"><a onclick="location.href=\''.$urlreimbill.'\'" class="text-semibold">Reimbursement Bill</a></div>
												</div>
											</td>
											<td><h6>'.$rowcntgen.'</h6></td>
										</tr>';
					break;

				case '10':
					$rowcntgriev=$rowcntgen;
					$urlgriev="spp_v4_web/bin-release/frm_GrievanceApprove.php";
					$pendingrequisitions.='<tr>
											<td>
												<div class="media-left">
													<div class="media-left"><a onclick="location.href=\''.$urlgriev.'\'" class="text-semibold">'.$gricaption.'</a></div>
												</div>
											</td>
											<td><h6>'.$rowcntgen.'</h6></td>
										</tr>';
					break;
			}

			$inti++;
		}
	}

	$reqmodals="";

	//HR Leave Summary
	include_once("spp_v4_web/bin-release/dbHRAuthorityList_b2.php");
	if (strpos($hrauthoritylist,"HRAUTHORITY")>0)
	{
		$reqstatus='<?xml version="1.0" standalone="yes"?>';
		$reqstatus=$reqstatus.$hrauthoritylist;		
		$xmlreqstatus = new SimpleXMLElement($reqstatus);
		$rowcnt=count($xmlreqstatus->HRAUTHORITY);
		$rowcnthrlevsum=$rowcnt;
	}

	if ($rowcnt>0)
	{
		$reqmodals.='<div id="modal_leavesum" class="modal fade"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h5 class="modal-title">Leave Summary</h5></div><div class="modal-body"><div class="table-responsive" style="height:360px;"><div id="table-leavesum"><table class="table"><thead><th>Sl. No.</th><th>Authority User Name</th><th>Authority</th><th>Assigned Emp</th><th>Pending Leaves</th><th>Total Approved</th><th>Total Rejected</th><th>Total Taken</th></thead><tbody>';
		for($i=0;$i<$rowcnt;$i++)
		{
			$reqmodals.= "<tr>";
			$reqmodals.= '<td>'.$xmlreqstatus->HRAUTHORITY[$i]->SLNO.'</td>';
		    $reqmodals.='<td>'.$xmlreqstatus->HRAUTHORITY[$i]->UNAME.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->HRAUTHORITY[$i]->GRP_DESC.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->HRAUTHORITY[$i]->TOTALEMPLIST.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->HRAUTHORITY[$i]->PENDING.'</td>';
		    $reqmodals.='<td>'.$xmlreqstatus->HRAUTHORITY[$i]->APPROVED.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->HRAUTHORITY[$i]->REJECTED.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->HRAUTHORITY[$i]->TOTAL.'</td>';
			$reqmodals.= "</tr>";	
		}
		$reqmodals.= '</tbody></table></div></div></div><div class="modal-footer"><button type="button" class="btn btn-primary" data-dismiss="modal">Close</button></div></div></div></div>';
	}

	//HR Leave Details
	include_once("spp_v4_web/bin-release/dbHRLevDetail_b2.php");
	if (strpos($hrlevdetail,"DBHR")>0)
	{
		$reqstatus='<?xml version="1.0" standalone="yes"?>';
		$reqstatus=$reqstatus.$hrlevdetail;		
		$xmlreqstatus = new SimpleXMLElement($reqstatus);
		$rowcnt=count($xmlreqstatus->DBHR);
		$rowcnthrlevdet=$rowcnt;
	}

	if ($rowcnt>0)
	{
		$reqmodals.='<div id="modal_leavedet" class="modal fade">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<legend><button type="button" class="close" data-dismiss="modal">&times;</button><i class="icon-hour-glass2"></i> Leave Pending for Approval</legend>
								</div>
								<div class="modal-body">
									<div class="table-responsive" style="height:360px;">
										<div id="table-leavedet">
											<table class="table" id="tbl_lev_pending">
												<thead class="bg-blue">
													<th>Sl. No.</th>
													<th>'.$_SESSION['EMPIDCAP'].'</th>
													<th>'.$_SESSION['REFNOCAP'].'</th>
													<th>Employee Name</th>
													<th>Leave Date</th>
													<th>Sanction Name</th>
													<th>Authority</th>
												</thead>
												<tbody>';
		for($i=0;$i<$rowcnt;$i++)
		{
			$reqmodals.= "<tr>";
			$reqmodals.= '<td>'.$xmlreqstatus->DBHR[$i]->SLNO.'</td>';
		    $reqmodals.='<td>'.$xmlreqstatus->DBHR[$i]->EMPID.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->DBHR[$i]->REFNO.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->DBHR[$i]->EMPNAME.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->DBHR[$i]->LEVDATE.'</td>';
		    $reqmodals.='<td>'.$xmlreqstatus->DBHR[$i]->UNAME.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->DBHR[$i]->GRP_DESC.'</td>';
			$reqmodals.= "</tr>";	
		}
		$reqmodals.= '</tbody>
						</table></div></div></div><div class="modal-footer">
					<button type="button" class="btn btn-primary" onclick="send_mail_sanc()"> Send Mail <i class="icon-paperplane"></i></button>
					<button type="button" class="btn btn-primary" data-dismiss="modal">Close <i class="icon-cancel-circle2"></i></button></div></div></div></div>';
	}

	//Experience List
	include_once("spp_v4_web/bin-release/frmSearchEmpCompleteYears.php");
	if (strpos($profilevaluesearch,"EMPDETAILS")>0)
	{
		$reqstatus='<?xml version="1.0" standalone="yes"?>';
		$reqstatus=$reqstatus.$profilevaluesearch;		
		$xmlreqstatus = new SimpleXMLElement($reqstatus);
		$rowcnt=count($xmlreqstatus->EMPDETAILS);
		$rowcntexplist=$rowcnt;
	}

	if ($rowcnt>0)
	{
		$reqmodals.='<div id="modal_explist" class="modal fade"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h5 class="modal-title">Experience List</h5></div><div class="modal-body"><div class="table-responsive" style="height:360px;"><div id="table-explist"><table class="table"><thead><th>'.$_SESSION['EMPIDCAP'].'</th><th>'.$_SESSION['REFNOCAP'].'</th><th>Employee Name</th><th>Exp. Years</th><th>On Date</th></thead><tbody>';
		for($i=0;$i<$rowcnt;$i++)
		{
			$reqmodals.= "<tr>";
		    $reqmodals.='<td>'.$xmlreqstatus->EMPDETAILS[$i]->EMPID.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->EMPDETAILS[$i]->REFNO.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->EMPDETAILS[$i]->EMPNAME.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->EMPDETAILS[$i]->YEARSINFO.'</td>';
		    $reqmodals.='<td>'.$xmlreqstatus->EMPDETAILS[$i]->LOGDATE.'</td>';
			$reqmodals.= "</tr>";	
		}
		$reqmodals.= '</tbody></table></div></div></div><div class="modal-footer"><button type="button" class="btn btn-primary" data-dismiss="modal">Close</button></div></div></div></div>';
	}

	//Probationary List
	include_once("spp_v4_web/bin-release/frmSearchEmp_probationary_modal.php");
	if (strpos($profilevaluesearch1,"EMPDETAILS")>0)
	{
		$reqstatus='<?xml version="1.0" standalone="yes"?>';
		$reqstatus=$reqstatus.$profilevaluesearch1;		
		$xmlreqstatus = new SimpleXMLElement($reqstatus);
		$rowcnt=count($xmlreqstatus->EMPDETAILS);
		$rowcntproblist=$rowcnt;
	}

	if ($rowcnt>0)
	{
		$reqmodals.='<div id="modal_problist" class="modal fade"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h5 class="modal-title">Probationary List</h5></div><div class="modal-body"><div class="table-responsive" style="height:360px;"><div id="table-problist"><table class="table"><thead><th>'.$_SESSION['EMPIDCAP'].'</th><th>'.$_SESSION['REFNOCAP'].'</th><th>Employee Name</th><th>Probationary Period</th></thead><tbody>';
		for($i=0;$i<$rowcnt;$i++)
		{
			$reqmodals.= "<tr>";
		    $reqmodals.='<td>'.$xmlreqstatus->EMPDETAILS[$i]->EMPID.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->EMPDETAILS[$i]->REFNO.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->EMPDETAILS[$i]->EMPNAME.'</td>';
		    $reqmodals.= '<td>'.$xmlreqstatus->EMPDETAILS[$i]->PROBATIONDATE.'</td>';
			$reqmodals.= "</tr>";	
		}
		$reqmodals.= '</tbody></table></div></div></div><div class="modal-footer"><button type="button" class="btn btn-primary" data-dismiss="modal">Close</button></div></div></div></div>';
	}

	echo $reqmodals;

?>
<!-- Header Part Ends -->
<script src="assets/js/profimage_upload.js"></script>
<script>
	setTimeout(function() {
	$('.message').fadeOut('slow');
	}, 1500);
</script>
<!-- Main navbar -->
<?php include 'common/menu.php'; ?>
<!-- /main navbar -->

<!-- Page container -->
	<div class="page-container">

		<!-- Page content -->
			<div class="page-content">

                <!-- Main sidebar -->
                	<?php include 'common/sidebar.php'; ?>
                <!-- /main sidebar -->

			<!-- Main content -->
			<div class="content-wrapper">

				<!-- Page header -->
					<?php include 'common/pageheader.php'; ?>
				<!-- /page header -->

				<!-- Content area -->
				<div class="content">

					<!-- Main Content -->
					<div class="row">
						<div class="col-lg-12">                        
                                <!-- Dashboard content -->
							<div class="row">
				                <div class="col-sm-4">
				                	<div class="panel text-center" style="height:280px;">
				                		<div class="panel-body text-center">
				                			<h6><i class='fa fa-bell'></i> <span><b>Unapproved Requisitions</b></span></h6>
				                			<div class="table-responsive" style="height:200px;">
												<table class="table text-nowrap">
													<thead>
														<tr>
															<th>Requisition Type</th>
															<th class="col-md-2">Count</th>
														</tr>
													</thead>
													<tbody>
														<?php echo $pendingrequisitions; 
														echo "<tr id=empprofile></tr>"
														?>
														
													</tbody>
												</table>
											</div>
				                		</div>
				                	</div>
				                </div>

				                <div class="col-sm-2">
				                	<button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:140px;" data-toggle="modal" data-target="#modal_leavesum" <?php if($rowcnthrlevsum==0) echo 'disabled'; ?> ><i class="icon-calendar3"></i> <span><b>Leave Approval Summary</b></span></button>

				                	<button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:120px;" data-toggle="modal" data-target="#modal_leavedet" <?php if($rowcnthrlevdet==0) echo 'disabled'; ?> ><i class="icon-user-check"></i> <span><b>Leave(s) Approval Pending from Authority</b></span></button>
				                </div>

				                <div class="col-sm-6">
				                	<div class="panel text-center" style="height:280px;">
				                		<div class="panel-body text-center">
				                			<label><i class='icon-users4'></i> <span><b>EMPLOYEE(S) LIST</b></span></label>
				                			<div class="table-responsive" style="height:200px;">
												<table class="table text-nowrap">
													<thead class="bg-blue">
														<tr>
															<th><?php echo $_SESSION['EMPIDCAP']; ?></th>
															<th><?php echo $_SESSION['REFNOCAP']; ?></th>
															<th>Name</th>
															<th>View Summary</th>
															<th>Apply Leave</th>
														</tr>
													</thead>
													<tbody>
													<?php echo $emplist; ?>
													</tbody>
												</table>
											</div>
				                		</div>
				                	</div>
				                </div>
                            </div>

                            <div class="row">
                            	<div class="col-sm-2">
				                	<!--<button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:185px;" data-toggle="modal" data-target="#modal_explist"><i class="icon-users"></i> <span><b>View Experience List</b></span></button>-->
                                    <button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:82px;" data-toggle="modal" data-target="#modal_explist"><i class="icon-users"></i> <span><b>View Experience List</b></span></button>
                                   <a href="spp_v4_web/bin-release/hr_joinedleftemp_ui.php" > <button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:82px;" ><i class="icon-file-media mr-3 icon-2x"></i> <span><b>Joined And Left Employee List</b></span></button></a>
				                </div>
								<!-- HEAD COUNT SUMMARY ADDED BY GEETHANJALI MK -- 06/03/2019 -->
								<div class="col-sm-2">
				                	<button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:82px;" data-toggle="modal" data-target="#modal_problist"><i class="icon-file-text2"></i> <span><b>View Probationary List</b></span></button>
									
									<button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:82px;" data-toggle="modal" data-target="#modal_headcntsummary"><i class="icon-profile"></i> <span><b>Head Count Summary</b></span></button>
									
									<div id="modal_headcntsummary" class="modal fade">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header">
													<button type="button" class="close" data-dismiss="modal">&times;</button>
													<legend class="modal-title"><i class="icon-profile"></i> Head Count Summary</legend>
												</div>
												<div class="modal-body">
													<div class="panel panel-default">
														<div class="panel-body">
															<!--------------------------------------------------------------------------->
															<div class="tabbable">
																<ul class="nav nav-tabs nav-tabs-solid nav-tabs-component nav-justified">
																	<li class="active "><a href="#dept_tab" data-toggle="tab" aria-expanded="false">Department</a></li>
																	<li class=""><a href="#desg_tab" data-toggle="tab" aria-expanded="true">Designation</a></li>
																	<li class=""><a href="#bran_tab" data-toggle="tab" aria-expanded="true">Branch</a></li>
																</ul>
															
																<div class="tab-content">
																	<div class="tab-pane active" id="dept_tab"><br>
																		<div class="table-responsive" style="height:250px">
																			<table class="table table-xs">
																				<thead class="bg-grey-300">
																					<th>Department</th>
																					<th>Employee(s) Count</th>
																				</thead>
																				<tbody>
																				<?php
																					$query="select distinct DEPARTMENT from mas_department";
																					$result=mysqli_query($link_id,$query);
																					$count=mysqli_num_rows($result);
																					if ($count > 0)
																					{
																						while($query_data=mysqli_fetch_array($result))
																						{
																							$qry = "select count(*) AS EMPCOUNT from mas_users mu inner join 
																							(select e.EMPID,e.DEPARTMENT,e.DESIGNATION,emp.DOL from 
																							(select * from mas_employeedet) e  
																							inner join (select empid,max(empdetid) as empdetid from mas_employeedet group by empid) det on e.empdetid=det.empdetid 
																							and e.empid=det.empid inner join mas_employee emp on e.empid=emp.empid AND e.DEPARTMENT='".$query_data['DEPARTMENT']."') as emp 
																							on mu.EMPID=emp.EMPID and ACTIVEYN=1 
																							AND GROUPID=6 AND (emp.DOL IS NULL OR emp.DOL <= CURDATE())";
																							$res=mysqli_fetch_array(mysqli_query($link_id,$qry));
																							echo '<tr>
																									<td>'.$query_data['DEPARTMENT'].'</td>
																									<td>'.$res['EMPCOUNT'].'</td>
																								</tr>';
																						}
																					}
																				?>
																				</tbody>
																			</table>
																		</div>	
																	</div>

																	<div class="tab-pane" id="desg_tab"><br>
																		<div class="table-responsive" style="height:250px">
																			<table class="table table-xs">
																				<thead class="bg-grey-300">
																					<th>Designation</th>
																					<th>Employee(s) Count</th>
																				</thead>
																				<tbody>
																				<?php
																					$query="SELECT DISTINCT DESIGNATION from mas_designation";
																					$result=mysqli_query($link_id,$query);
																					$count=mysqli_num_rows($result);
																					if ($count > 0)
																					{
																						while($query_data=mysqli_fetch_array($result))
																						{
																							$qry = "select count(*) AS EMPCOUNT from mas_users mu inner join 
																							(select e.EMPID,e.DEPARTMENT,e.DESIGNATION,emp.DOL from 
																							(select * from mas_employeedet) e  
																							inner join (select empid,max(empdetid) as empdetid from mas_employeedet group by empid) det on e.empdetid=det.empdetid 
																							and e.empid=det.empid inner join mas_employee emp on e.empid=emp.empid AND e.DESIGNATION='".$query_data['DESIGNATION']."') as emp 
																							on mu.EMPID=emp.EMPID and ACTIVEYN=1 
																							AND GROUPID=6 AND (emp.DOL IS NULL OR emp.DOL <= CURDATE())";
																							$res=mysqli_fetch_array(mysqli_query($link_id,$qry));
																							echo '<tr>
																									<td>'.$query_data['DESIGNATION'].'</td>
																									<td>'.$res['EMPCOUNT'].'</td>
																								</tr>';
																						}
																					}
																				?>
																				</tbody>
																			</table>
																		</div>
																	</div>

																	<div class="tab-pane" id="bran_tab"><br>
																		<div class="table-responsive" style="height:250px"><br>
																			<table class="table table-xs">
																				<thead class="bg-grey-300">
																					<th>Branch</th>
																					<th>Employee(s) Count</th>
																				</thead>
																				<tbody>
																				<?php
																					$query="SELECT * FROM mas_branch";
																					$result=mysqli_query($link_id,$query);
																					$count=mysqli_num_rows($result);
																					if ($count > 0)
																					{
																						while($query_data=mysqli_fetch_array($result))
																						{
																							$qry = "select count(*) AS EMPCOUNT from mas_users mu inner join 
																							(select e.EMPID,b.BRANCHNAME,emp.DOL from 
																							(select * from mas_employeedet) e  
																							inner join mas_branch b on e.BRANCHID=b.BRANCHID 
																							inner join (select empid,max(empdetid) as empdetid from mas_employeedet group by empid) det on e.empdetid=det.empdetid 
																							and e.empid=det.empid inner join mas_employee emp on e.empid=emp.empid AND b.BRANCHID=".$query_data['BRANCHID'].") as emp on mu.EMPID=emp.EMPID and ACTIVEYN=1 
																							AND GROUPID=6 AND (emp.DOL IS NULL OR emp.DOL <= CURDATE())";
																							$res=mysqli_fetch_array(mysqli_query($link_id,$qry));
																							echo '<tr>
																									<td>'.$query_data['BRANCHNAME'].'</td>
																									<td>'.$res['EMPCOUNT'].'</td>
																								</tr>';
																						}
																					}
																				?>
																				</tbody>
																			</table>
																		</div>
																	</div>
																</div>
															</div>
															<!--------------------------------------------------------------------------->
														</div>
													</div>
												</div>
												<div class="modal-footer">	
													<button type="button" class="btn btn-primary" onclick="location.href='spp_v4_web/bin-release/headcntsummary_ui.php'">View Details <i class="icon-info22"></i></button>
													<button type="button" class="btn btn-primary" data-dismiss="modal">Close <i class="icon-cross3"></i></button>
												</div>
											</div>
										</div>
									</div>
				                </div>

				                <div class="col-sm-4">
				                	<div class="panel text-center" style="height:185px;">
				                		<div class="panel-body text-center">
				                			<i class='fa fa-user-times'></i> <span><b>Members on Leave Today</b> <button  align="right" type="button"  class="btn btn-primary" style="height:20px;"  onclick="location.href='spp_v4_web/bin-release/rpt_leaveontoday.php'" target="_blank" >Report</button></span>
				                			<div class="table-responsive" style="height:140px;">
												<table class="table text-nowrap">
													<thead>
														<tr>
															<th></th>
															<th></th>
														</tr>
													</thead>
													<tbody>
														<?php echo $memonleavetoday; ?>
													</tbody>
												</table>
											</div>
				                		</div>
				                	</div>
				                </div>

				                <div class="col-sm-2">
				                	<button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:82px;" onclick="location.href='spp_v4_web/bin-release/calendar.php'"><i class="icon-calendar22"></i> <span><b>Leave Calendar</b></span></button>

				                	<button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:83px;" onclick="location.href='spp_v4_web/bin-release/LeaveForceApproval_ui.php'"><i class="icon-stack-check"></i> <span><b>Force Approve/Reject</b></span></button>
				                </div>

				                <div class="col-sm-2">
				                	<button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:82px;" onclick="location.href='spp_v4_web/bin-release/holidaylist_sanction_HR.php'"><i class="icon-airplane2"></i> <span><b>Holidays List</b></span></button>

				                	<button type="button" class="panel text-center btn btn-block btn-float btn-float-lg val" style="height:83px;" onclick="location.href='spp_v4_web/bin-release/hr_reqfilterreport_ui.php'"><i class="icon-file-text2"></i> <span><b>Filter Requisition</b></span></button>
				                </div>				                
                            </div>
	                                <!-- /dashboard content -->	                                
						</div>
					</div>
					<!-- /main Content -->
<!--------------------- Divya - 05/03/2019 - Reminder for Probation and Confirmation Date ------------->
                   
<div id="probation" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">								
            <button type="button" class="close" data-dismiss="modal">&times;</button>									
        </div>
        <div class="modal-body">
			<div class="col-lg-12" >
            <div class="panel panel-flat">
				<div class="panel panel-body">
					<div class="media-body">
						<legend class="text-semibold text-info"><i class="fa fa-users mr-2"></i>   Employee Probation And  Confirmation Details<span style="float:right"><button type="button" class="btn btn-link " data-dismiss="modal"><i class="fa fa-close mr-2"></i></button></span></legend>  
                        <div class="tabbable">
        					<ul class="nav nav-tabs nav-tabs-highlight">
        						<li class="active"><a href="#probtab" data-toggle="tab"><strong>Probation Completion Details</strong></a></li>
       							 <li><a href="#confirmationtab" data-toggle="tab"><strong>Confirmation Details</strong></a></li>
       						 </ul>
        					 <div class="tab-content">
       							 <div class="tab-pane active" id="probtab">
                                  <?php
								echo ' <br><strong class="text-info">Employee(s) Completing Probation Period Today</strong><br>';
								  $todaydate=date('Y-m-d');
                                  $qr=mysqli_query($link_id,"Select * from mas_users mu inner join mas_employee memp on memp.EMPID=mu.EMPID
                                                inner join mas_employeedet mempdet on mempdet.EMPID=mu.EMPID 
                                                where mu.ACTIVEYN=1  and memp.DOL is null and memp.PROBATIONDATE='".$todaydate."' group by mu.EMPID");
                                                $prob .= "<br><div class='table-responsive' ><table class='table'><thead class='bg-blue'><th>Sl.No.</th><th>".$_SESSION['EMPIDCAP']."</th><th>".$_SESSION['REFNOCAP']."</th><th>Employee Name</th><th> Completion Date</th></thead><tbody>";
                                                $slnoval=1;
												echo "<input type='hidden' id='probcnt' value=".mysqli_num_rows($qr).">";
												//if(mysqli_num_rows($q)>0)
												if(mysqli_num_rows($qr)>0)
												{
													while($r=mysqli_fetch_array($qr))
													{
														/*$emp_val=$r['EMPID'];
														$dol_val=$r['PROBATIONDATE'];
														$doj=$r['DOJ'];
														if ($dol_val == '')
														{
															$dol=date('Y/m/d');
															$dol_value=date('d/m/Y');
														}
														else
														{
															$dol=$dol_val;
														}
														$diff=dateDiffProb('/',$dol,$doj);*/
														
														$prob .= "<tr><td>".$slnoval."</td>";
														$prob .= "<td>".$r['EMPID']."</td>";
														$prob .= "<td>".$r['REFNO']."</td>";
														$prob .= "<td>".$r['EMPNAME']."</td>";
														$prob .= "<td>".date('d/m/Y',strtotime($r['PROBATIONDATE']))."</td>";
														$slnoval++;
													}
													$prob .= "</tr></tbody></table></div>";
													echo $prob;   
												}
												else
												{
													$prob= "<br><p align='center'><strong>No Data Found</strong></p>";
													echo $prob;   
												}
                                               //echo $prob;                                         
                                           ?>
                                 
                                 
                                 </div>
        			<div class="tab-pane" id="confirmationtab">
					<?php
					echo ' <br><strong class="text-info"> Confirmed Employee(s) Details</strong></br>';
                    $q1=mysqli_query($link_id,"Select * from mas_users mu inner join mas_employee memp on memp.EMPID=mu.EMPID
                    inner join mas_employeedet mempdet on mempdet.EMPID=mu.EMPID 
                    where mu.ACTIVEYN=1  and memp.DOL is null and memp.CONFIRMATIONDATE='".$todaydate."' group by mu.EMPID");
                    $confirmation .= "<br><div class='table-responsive' ><table class='table'><thead class='bg-blue'><th>Sl.No.</th><th>".$_SESSION['EMPIDCAP']."</th><th>".$_SESSION['REFNOCAP']."</th><th>Employee Name</th><th>Confirmation Date</th></thead><tbody>";
                    $slnoval=1;
                    
                    $cntbb=mysqli_num_rows($q1);
					echo "<input type='hidden' id='confcnt' value=".mysqli_num_rows($q1).">";
                    if($cntbb==0)
                    {	
                   		  $confirmation= "<br><p align='center'><strong>No Data Found</strong></p>";
						 // echo  $confirmation;
                    }
                    else
                    {
						while($r1=mysqli_fetch_array($q1))
						{
							$confirmation .= "<tr><td>".$slnoval."</td>";
							$confirmation .= "<td>".$r1['EMPID']."</td>";
							$confirmation .= "<td>".$r1['REFNO']."</td>";
							$confirmation .= "<td>".$r1['EMPNAME']."</td>";
							$confirmation .= "<td>".date('d/m/Y',strtotime($r1['CONFIRMATIONDATE']))."</td>";
							$slnoval++;
						}
						$confirmation .= "</tr></tbody></table></div>";
                  }
				  echo  $confirmation;
                     
                    ?>
       				 </div>
       			 </div>
       		 </div>
          </div>
          </div>
      <!-- </div>
	</div>
</div>-->

    <div class="modal-footer">
        <div class="col-lg-6" align="left" >
        	<input type="checkbox" align="left" id="probation_hide" onclick="closeprobation()"> <strong>Do not show again</strong>
        </div>
        <div class="col-lg-6">
       	<button type="button" class="btn  bg-blue" data-dismiss="modal">Close <i class="fa fa-close mr-2"></i></button>
        </div>
    </div>
        		</div>
       		 </div>
        </div>
    </div>
					
<!---------------------  Divya - 05/03/2019 -End Reminder for Probation and Confirmation Date ------------->                    
                    
                    
<!-- Footer -->
     <?php include 'common/footer.php'; ?>
<!-- /Footer -->
<?php
	$muid=$_SESSION['MAIN_MUID'];
	$grpid=$_SESSION['groupid'];
?>
<script>
window.onload=emp_pro_upd_cnt();

function emp_pro_upd_cnt()
{
	//----------Reminder for Probation and Confirmation Date of employees Divya-05/03/2019 --------------//
	if(<?php echo $_SESSION['SHOWPROBATION']; ?> == 1)
	{
		//alert(document.getElementById('confcnt').value);
		//alert(document.getElementById('probcnt').value);
		if((document.getElementById('confcnt').value>0 ) || (document.getElementById('probcnt').value>0))
		{
			$('#probation').modal('show');
		}
	}
	//---------------------------------------------------------------------------------------------------//
	var muidval=<?php echo $muid; ?>;
	var groupidval=<?php echo $grpid; ?>;
	var passdata={MUID:muidval,GRP_ID:groupidval,ALL:"false",RADIO:"1"};
	var emplistctrl="";
	$.ajax({
		  type: 'POST',
		  url: "spp_v4_web/bin-release/hrupdateReqcnt.php",
		  data: passdata,
		  dataType: "text",
		  success: function(response,status)
		  {
				var parser, xmlDoc;
				var text = response;
				parser = new DOMParser();
				xmlDoc = parser.parseFromString(text,"text/xml");
				
				var x=xmlDoc.getElementsByTagName('EMP_ROOT')[0].childNodes;
				var cnt=x.length;
				if(cnt > 0)
				{
					var cnturl = "spp_v4_web/bin-release/myprofile_sanction_ui.php";
					var con = " <tr><td><div class='media-left'><div class='media-left'><a href='"+ cnturl +"'class='text-semibold'>Emp Profile Update</a></div></div></td><td><h6>"+ cnt +"</h6></td></tr>";
					document.getElementById("empprofile").innerHTML = con;
				}
		  }
	});									
}

	function send_mail_sanc()
	{
		$('#processing_modal').modal('show');
		$.ajax({
			type : 'post',
			url : 'spp_v4_web/bin-release/hr_send_mail_to_sanc.php',
			success : function(response){
				$('#processing_modal').modal('hide');
				alertmsgupd(response);
				setTimeout(function(){window.location.reload();},1000);
			}
		});
	}
	
	function closeprobation()
	{
		var	probation_hide;
		var probation_hide = document.getElementById("probation_hide").checked;	
	
		//var postData = {BDAYHIDE:welcome_hide};
	
		var saveData = $.ajax({
			type: 'POST',
			url: "reminderhide.php?ACTION=probation",
			data: '',
			dataType: "text",
			success: function(response,status){
	 
			}
	 
		});
	}
</script>