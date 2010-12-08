<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../'\">login</a> to access this page.");
  exit;
}
?>

<?php
   $_POST[page_name] = "Activit&eacute; par t&acirc;che"; 
   include '../header.inc.php'; 
?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>


<script language="JavaScript">
  function submitForm() {
    document.forms["form1"].bugid.value = document.getElementById('bugidSelector').value;
    document.forms["form1"].action.value = "displayBug";
    document.forms["form1"].submit();
  }
</script>

<div id="content">

<?php

include_once "../constants.php";
include_once "../tools.php";
include_once "../reports/issue.class.php";
include_once "../timetracking/time_track.class.php";
include_once "../auth/user.class.php";

// ---------------------------------------------------------------
function displayIssueSelectionForm($user1, $defaultBugid) {
   echo "<div align=center>\n";
	echo "<form id='form1' name='form1' method='post' action='issue_info.php'>\n";

  echo "Task: \n";
   
  // This filters the bugid list to shorten the 'bugid' Select.
  //$user1 = new User($user_id);
  $taskList = $user1->getPossibleWorkingTasksList();
  
  $managedProjTaskList = $user1->getPossibleWorkingTasksList($user1->getProjectList($user1->getManagedTeamList()));
  
  $taskList += $managedProjTaskList;
  
  echo "<select id='bugidSelector' name='bugidSelector'>\n";
  foreach ($taskList as $bid)
  {
    $issue = new Issue ($bid);
    if ($bid == $defaultBugid) {
      echo "<option selected value='".$bid."'>".$bid." / $issue->tcId : $issue->summary</option>\n";
    } else {
      echo "<option value='".$bid."'>".$bid." / $issue->tcId : $issue->summary</option>\n";
    }
  }
  echo "</select>\n";
   
  echo "<input type=button value='Envoyer' onClick='javascript: submitForm()'>\n";
   
  echo "<input type=hidden name=action value=noAction>\n";
  echo "<input type=hidden name=bugid  value=$defaultBugid>\n";
   
  echo "</form>\n";
  echo "</div>\n";
}
   
// ---------------------------------------------------------------
function displayIssueGeneralInfo($issue) {      
  echo "<table>\n";

  echo "<tr>\n";
  echo "<th>Status</th>\n";
  echo "<td>".$issue->getCurrentStatusName()."</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<th title='BI + BS'>Estimated</th>\n";
  echo "<td title='$issue->effortEstim + $issue->effortAdd'>".($issue->effortEstim + $issue->effortAdd)."</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<th>Elapsed</th>\n";
  echo "<td>".$issue->elapsed."</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<th>Remaining</th>\n";
  echo "<td>$issue->remaining</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<th>Drift</th>\n";
  $derive = $issue->getDrift();
  echo "<td style='background-color: ".$issue->getDriftColor($derive)."'>".$derive."</td>\n";
  echo "</tr>\n";
   
  echo "</table>\n";      
}


// ---------------------------------------------------------------
function displayMonth($month, $year, $issue) {
  global $globalHolidaysList;
  global $job_study;
  global $job_analyse;
  global $job_dev;  
  global $job_test;
  global $job_none;
  global $job_colors;
  
  // if no work done this month, do not display month
  $trackList = $issue->getTimeTracks();
  $found = 0;
  foreach ($trackList as $tid => $tdate) {
    if ($month == date('m', $tdate)) {
      $found += 1;
      break; 
    }
  }
  if (0 == $found) { return; }
   
  $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
  $monthFormated = date("F Y", $monthTimestamp); 
  $nbDaysInMonth = date("t", $monthTimestamp);

  echo "<div class='center'>\n";
  echo "<table width='70%'>\n";
  echo "<caption>$monthFormated</caption>\n";
  echo "<tr>\n";
  echo "<th></th>\n";
  for ($i = 1; $i <= $nbDaysInMonth; $i++) {
    if ($i < 10 ) {
      echo "<th>0$i</th>\n";
    }
    else {
      echo "<th>$i</th>\n";
    }
  }
  echo "</tr>\n";
   
  $userList = $issue->getInvolvedUsers();
  foreach ($userList as $uid => $username) {
    
    // build $durationByDate[] for this user	
    $userTimeTracks = $issue->getTimeTracks($uid);
    $durationByDate = array();
    $jobColorByDate = array();
    foreach ($userTimeTracks as $tid => $tdate) {
      $tt = new TimeTrack($tid);
    	$durationByDate[$tdate] += $tt->duration;
    	$jobColorByDate[$tdate] = $job_colors[$tt->jobId];
    }

   // ------
    echo "<tr>\n";
    echo "<td>$username</td>\n";
        
    for ($i = 1; $i <= $nbDaysInMonth; $i++) {
      $todayTimestamp = mktime(0, 0, 0, $month, $i, $year);
      $dayOfWeek = date("N", $todayTimestamp);
      
      if (NULL != $durationByDate[$todayTimestamp]) {
        echo "<td style='background-color: ".$jobColorByDate[$todayTimestamp]."; text-align: center;'>".$durationByDate[$todayTimestamp]."</td>\n";
      } else {
        // if weekend or holiday, display gray
        if (($dayOfWeek > 5) || 
            (in_array(date("Y-m-d", $todayTimestamp), $globalHolidaysList))) { 
          echo "<td style='background-color: #d8d8d8;'></td>\n";
        } else {
          echo "<td></td>\n";
        }
      }
    }
    echo "</tr>\n";
  }
  echo "</table>\n";
  echo "<br/><br/>\n";
}




// ================ MAIN =================
$year = date('Y');

$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) or die("Could not connect database : ".mysql_error());
mysql_select_db($db_mantis_database) or die("Could not select database : ".mysql_error());

$action = $_POST[action];
$session_userid = isset($_POST[userid]) ? $_POST[userid] : $_SESSION['userid'];
$bug_id = isset($_POST[bugid])  ? $_POST[bugid]  : 0;

$user = new User($session_userid);


$lTeamList = $user->getLeadedTeamList();
$managedTeamList = $user->getManagedTeamList();
$teamList = $lTeamList + $managedTeamList;

if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
	echo ("Sorry, you need to be member of a Team to access this page.");
   echo "</div>";

} else {

	displayIssueSelectionForm($user, $bug_id);
	
	if ("displayBug" == $action) {
	  $issue = new Issue ($bug_id);
	        
	  echo "<br/><br/>\n";
	  echo "<br/>";
	  displayIssueGeneralInfo($issue);
	  echo "<br/><br/>\n";
	  
	  for ($i = 1; $i <= 12; $i++) {
	    displayMonth($i, $year, $issue);
	  }
	
	  echo "<br/><br/>\n";
	}
}
?>

</div>

<?php include '../footer.inc.php'; ?>