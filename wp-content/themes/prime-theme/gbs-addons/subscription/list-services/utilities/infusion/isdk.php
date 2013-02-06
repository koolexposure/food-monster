<?php
##########################################################################
#########        Object Oriented PHP SDK for Infusionsoft        #########
#########           Created by Justin Morris on 09-10-08         #########
#########           Updated by Justin Gourley on 11-08-10        #########
#########           Updated by Justin Gourley on 03-31-11        #########
##########################################################################

include("xmlrpc-2.0/lib/xmlrpc.inc");

class iSDK {
////////////////////////////////
//////////CONNECTOR/////////////
////////////////////////////////


###Connect from an entry in the config file###
public function cfgCon($name,$dbOn="on") {
  $this->debug = $dbOn;
  $name = get_option(Group_Buying_Infusionsoft::APPNAME);
  $key = get_option(Group_Buying_Infusionsoft::APPKEY);
  if ($name) {
    $this->client = new xmlrpc_client("https://" . $name .
".infusionsoft.com/api/xmlrpc");
  }


  ###Return Raw PHP Types###
  $this->client->return_type = "phpvals";

  ###Dont bother with certificate verification###
  $this->client->setSSLVerifyPeer(FALSE);
  //$this->client->setDebug(2);
  ###API Key###
  $this->key = $key;

  if ($this->appEcho("connected?")) {
    return TRUE;
  } else { return FALSE; }

  return TRUE;
}

###Connect and Obtain an API key from a vendor key###
public function vendorCon($name,$user,$pass,$dbOn="on") {
  $this->debug = $dbOn;

  $name = get_option(Group_Buying_Infusionsoft::APPNAME);
  $key = get_option(Group_Buying_Infusionsoft::APPKEY);

  if ($name) {
    $this->client = new xmlrpc_client("https://" . $name .
".infusionsoft.com/api/xmlrpc");
  }

  ###Return Raw PHP Types###
  $this->client->return_type = "phpvals";

  ###Dont bother with certificate verification###
  $this->client->setSSLVerifyPeer(FALSE);

  ###API Key###
  $this->key = $key;

  $carray = array(
    php_xmlrpc_encode($this->key),
    php_xmlrpc_encode($user),
    php_xmlrpc_encode(md5($pass)));

  $this->key = $this->methodCaller("DataService.getTemporaryKey",$carray);

  if ($this->appEcho("connected?")) {
    return TRUE;
  } else { return FALSE; }

  return TRUE;
}

###Worthless public function, used to validate a connection###
public function appEcho($txt) {

    $carray = array(
                    php_xmlrpc_encode($txt));

    return $this->methodCaller("DataService.echo",$carray);
}
###Method Caller###
public function methodCaller($service,$callArray) {
    ###Set up the call###
    $call = new xmlrpcmsg($service, $callArray);
    ###Send the call###
    $result = $this->client->send($call);
    ###Check the returned value to see if it was successful and return it###
    if(!$result->faultCode()) {
      return $result->value();
    } else {
      if ($this->debug=="kill"){
        die("ERROR: " . $result->faultCode() . " - " .
$result->faultString());
      } elseif ($this->debug=="on") {
        return "ERROR: " . $result->faultCode() . " - " .
$result->faultString();
      } elseif ($this->debug=="off") {
        //ignore!
      }
    }
}


/////////////////////////////////////////////////////////
//////////////////// FILE  SERVICE ////////////////////// /////////////////////////////////////////////////////////
//Available in Version 18.x+
//String getFile(String key, int fileId) - returns base64 encoded file data
public function getFile($fileID) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$fileID));
    $result = $this->methodCaller("FileService.getFile",$carray);
    return $result;
}

//int uploadFile(String key, String fileName, String base64encoded) - returns file id
public function uploadFile($fileName,$base64Enc,$cid=0) {
    $result = 0;
    if($cid==0) {
      $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($fileName),
                    php_xmlrpc_encode($base64Enc));
      $result = $this->methodCaller("FileService.uploadFile",$carray);
    } else {
      $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode($fileName),
                    php_xmlrpc_encode($base64Enc));
      $result = $this->methodCaller("FileService.uploadFile",$carray);
    }
    return $result;
}

//boolean replaceFile(String key, int fileId, String base64encoded) - returns true if successful
public function replaceFile($fileID,$base64Enc) {
    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$fileID),
                    php_xmlrpc_encode($base64Enc));
    $result = $this->methodCaller("FileService.replaceFile",$carray);
    return $result;
}

//boolean renameFile(String key, int fileId, String fileName) - returns true if successful
public function renameFile($fileID,$fileName) {
    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$fileID),
                    php_xmlrpc_encode($fileName));
    $result = $this->methodCaller("FileService.renameFile",$carray);
    return $result;
}

//String getDownloadUrl(String key, int fileId)
public function getDownloadUrl($fileID) {
    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$fileID));
    $result = $this->methodCaller("FileService.getDownloadUrl",$carray);
    return $result;
}


/////////////////////////////////////////////////////////
////////////////////CONTACT SERVICE////////////////////// /////////////////////////////////////////////////////////
###public function to add contacts to Infusion - Returns Contact ID###
public function addCon($cMap, $optReason = "") {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($cMap,array('auto_dates')));
    $conID = $this->methodCaller("ContactService.add",$carray);
    if (!empty($cMap['Email'])) {
      if ($optReason == "") { $this->optIn($cMap['Email']); } else { $this->optIn($cMap['Email'],$optReason); }
    }
    return $conID;
}

###public function to Update Contacts in Infusion - Returns updated contacts ID###
public function updateCon($cid, $cMap) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode($cMap,array('auto_dates')));
    return $this->methodCaller("ContactService.update",$carray);
}

###Finds all contacts for an Email###
public function findByEmail($eml, $fMap) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($eml),
                    php_xmlrpc_encode($fMap));
    return $this->methodCaller("ContactService.findByEmail",$carray);
}

###public function to load a contacts data - Returns a Key/Value array###
public function loadCon($cid, $rFields) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode($rFields));
    return $this->methodCaller("ContactService.load",$carray);
}

###public function to add a contact to a group###
public function grpAssign($cid, $gid) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode((int)$gid));
    return $this->methodCaller("ContactService.addToGroup",$carray);
}

###public function to remove a contact from a group###
public function grpRemove($cid, $gid) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode((int)$gid));
    return $this->methodCaller("ContactService.removeFromGroup",$carray);
}

###public function to add a contact to a campaign###
public function campAssign($cid, $campId) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode((int)$campId));
    return $this->methodCaller("ContactService.addToCampaign",$carray);
}

###Returns next step in a campaign###
public function getNextCampaignStep($cid, $campId) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode((int)$campId));
    return
$this->methodCaller("ContactService.getNextCampaignStep",$carray);
}

###Returns step details for a contact in a campaign###
public function getCampaigneeStepDetails($cid, $stepId) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode((int)$stepId));
    return
$this->methodCaller("ContactService.getCampaigneeStepDetails",$carray);
}

###Reschedules a campaign step for a list of contacts###
public function rescheduleCampaignStep($cidList, $campId) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($cidList),
                    php_xmlrpc_encode((int)$campId));
    return
$this->methodCaller("ContactService.rescheduleCampaignStep",$carray);
}

###public function to remove a contact from a campaign###
public function campRemove($cid, $campId) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode((int)$campId));
    return $this->methodCaller("ContactService.removeFromCampaign",$carray);
}

###public function to pause a contacts campaign###
public function campPause($cid, $campId) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode((int)$campId));
    return $this->methodCaller("ContactService.pauseCampaign",$carray);
}

###public function to run an Action Sequence###
public function runAS($cid, $aid) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$cid),
                    php_xmlrpc_encode((int)$aid));
    return $this->methodCaller("ContactService.runActionSequence",$carray);
}

/////////////////////////////////////////////////////////
//////////////////////DATA SERVICE/////////////////////// /////////////////////////////////////////////////////////

//DataService.getAppSetting(key, module, setting)
public function dsGetSetting($module,$setting) {
    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($module),
                    php_xmlrpc_encode($setting));
    return $this->methodCaller("DataService.getAppSetting",$carray);
}


public function dsAdd($tName,$iMap) {
    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($tName),
                    php_xmlrpc_encode($iMap,array('auto_dates')));

    return $this->methodCaller("DataService.add",$carray);
}

public function dsDelete($tName,$id) {
    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($tName),
                    php_xmlrpc_encode((int)$id));

    return $this->methodCaller("DataService.delete",$carray);
}

###public function for DataService.update method###
public function dsUpdate($tName,$id,$iMap) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($tName),
                    php_xmlrpc_encode((int)$id),
                    php_xmlrpc_encode($iMap, array('auto_dates')));

    return $this->methodCaller("DataService.update",$carray);
}

###public function for DataService.load method###
public function dsLoad($tName,$id,$rFields) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($tName),
                    php_xmlrpc_encode((int)$id),
                    php_xmlrpc_encode($rFields));

    return $this->methodCaller("DataService.load",$carray);
}

###public function for DataService.findByField method###
public function dsFind($tName,$limit,$page,$field,$value,$rFields) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($tName),
                    php_xmlrpc_encode((int)$limit),
                    php_xmlrpc_encode((int)$page),
                    php_xmlrpc_encode($field),
                    php_xmlrpc_encode($value),
                    php_xmlrpc_encode($rFields));

    return $this->methodCaller("DataService.findByField",$carray);
}

###public function for DataService.query method###
public function dsQuery($tName,$limit,$page,$query,$rFields) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($tName),
                    php_xmlrpc_encode((int)$limit),
                    php_xmlrpc_encode((int)$page),
                    php_xmlrpc_encode($query,array('auto_dates')),
                    php_xmlrpc_encode($rFields));

    return $this->methodCaller("DataService.query",$carray);
}

###Adds a custom field to Infusionsoft###
public function addCustomField($context,$displayName,$dataType,$groupID) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($context),
                    php_xmlrpc_encode($displayName),
                    php_xmlrpc_encode($dataType),
                    php_xmlrpc_encode((int)$groupID));

    return $this->methodCaller("DataService.addCustomField",$carray);
}

###Authenticates a user account in Infusionsoft###
public function authenticateUser($userName,$password) {
    $password = strtolower(md5($password));
    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode($userName),
                    php_xmlrpc_encode($password));

    return $this->methodCaller("DataService.authenticateUser",$carray);
}

###Updates a custom field###
public function updateCustomField($fieldId, $fieldValues) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$fieldId),
                    php_xmlrpc_encode($fieldValues));
    return $this->methodCaller("DataService.updateCustomField",$carray);
}

/////////////////////////////////////////////////////////
////////////////////INVOICE SERVICE////////////////////// /////////////////////////////////////////////////////////

public function deleteInvoice($Id) {
    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$Id));
    return $this->methodCaller("InvoiceService.deleteInvoice",$carray);
}

public function deleteSubscription($Id) {
    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$Id));
    return $this->methodCaller("InvoiceService.deleteSubscription",$carray);
}

/*
public void setInvoiceSyncStatus(int id, boolean syncStatus); public void setPaymentSyncStatus(int id, boolean syncStatus); public String getPluginStatus(String fullyQualifiedClassName); public List getAllShippingOptions(); public Map getAllPaymentOptions(); public list getPayments(); */

###Get a list of payments on an invoice### ###Find the id of the invoice attached to a one-time order###
public function getPayments($Id) {
    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$Id));
    return $this->methodCaller("InvoiceService.getPayments",$carray);
}
//////////////////////////////////////////////////////////////////////////

###Find the id of the invoice attached to a one-time order###
public function setInvoiceSyncStatus($Id,$syncStatus) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$Id),
        php_xmlrpc_encode($syncStatus));
    return
$this->methodCaller("InvoiceService.setInvoiceSyncStatus",$carray);
}
//////////////////////////////////////////////////////////////////////////
public function setPaymentSyncStatus($Id,$Status) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$Id),
        php_xmlrpc_encode($Status));
    return
$this->methodCaller("InvoiceService.setPaymentSyncStatus",$carray);
}
///////////////////////////////////////////////////////////////////////////
public function getPluginStatus($className) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($className));
    return $this->methodCaller("InvoiceService.getPluginStatus",$carray);
}
///////////////////////////////////////////////////////////////////////////
public function getAllShippingOptions() {
    $carray = array(
        php_xmlrpc_encode($this->key));
    return
$this->methodCaller("InvoiceService.getAllShippingOptions",$carray);
}
///////////////////////////////////////////////////////////////////////////
public function getAllPaymentOptions() {
    $carray = array(
        php_xmlrpc_encode($this->key));
    return
$this->methodCaller("InvoiceService.getAllPaymentOptions",$carray);
}
//////////////////////////////////////////////////////////////////////


public function
manualPmt($invId,$amt,$payDate,$payType,$payDesc,$bypassComm) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$invId),
        php_xmlrpc_encode($amt),
        php_xmlrpc_encode($payDate,array('auto_dates')),
        php_xmlrpc_encode($payType),
        php_xmlrpc_encode($payDesc),
        php_xmlrpc_encode($bypassComm));
    return $this->methodCaller("InvoiceService.addManualPayment",$carray);
}

###public function to Override Order Commisions - InvoiceService.addOrderCommissionOverride###
public function commOverride($invId,$affId,$prodId,$percentage,$amt,$payType,$desc,$date) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$invId),
          php_xmlrpc_encode((int)$affId),
        php_xmlrpc_encode((int)$prodId),
        php_xmlrpc_encode($percentage),
        php_xmlrpc_encode($amt),
        php_xmlrpc_encode($payType),
        php_xmlrpc_encode($desc),
        php_xmlrpc_encode($date,array('auto_dates')));

    return
$this->methodCaller("InvoiceService.addOrderCommissionOverride",$carray);
}

###public function to add an item to an order - InvoiceService.addOrderItem###
public function addOrderItem($ordId,$prodId,$type,$price,$qty,$Desc,$Notes)
{

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$ordId),
        php_xmlrpc_encode((int)$prodId),
        php_xmlrpc_encode($type),
        php_xmlrpc_encode($price),
        php_xmlrpc_encode($qty),
        php_xmlrpc_encode($Desc),
        php_xmlrpc_encode($notes));

    return $this->methodCaller("InvoiceService.addOrderItem",$carray);
}

###public function to add payment plans to orders - InvoiceService.addPaymentPlan###
public function payPlan($ordId,$aCharge,$ccId,$merchId,$retry,$retryAmt,$initialPmt,$initialPmtDate,$planStartDate,$numPmts,$pmtDays) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$ordId),
        php_xmlrpc_encode($aCharge),
        php_xmlrpc_encode((int)$ccId),
        php_xmlrpc_encode($merchId),
        php_xmlrpc_encode($retry),
        php_xmlrpc_encode($retryAmt),
        php_xmlrpc_encode($initialPmt),
        php_xmlrpc_encode($initialPmtDate,array('auto_dates')),
        php_xmlrpc_encode($planStartDate,array('auto_dates')),
        php_xmlrpc_encode($numPmts),
        php_xmlrpc_encode($pmtDays));

    return $this->methodCaller("InvoiceService.addPaymentPlan",$carray);
}

###public function to Override Recurring Order Commisions - InvoiceService.addOrderCommissionOverride###
public function recurringCommOverride($recId,$affId,$amt,$payType,$desc) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$recId),
        php_xmlrpc_encode((int)$affId),
        php_xmlrpc_encode($amt),
        php_xmlrpc_encode($payType),
        php_xmlrpc_encode($desc));

    return
$this->methodCaller("InvoiceService.addRecurringCommissionOverride",$carray)
;
}

###public function to add a recurring order - InvoiceService.addRecurringOrder###
public function addRecurring($cid,$allowDup,$progId,$merchId,$ccId,$affId,$daysToCharge) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$cid),
        php_xmlrpc_encode($allowDup),
        php_xmlrpc_encode((int)$progId),
        php_xmlrpc_encode((int)$merchId),
        php_xmlrpc_encode((int)$ccId),
        php_xmlrpc_encode((int)$affId),
        php_xmlrpc_encode($daysToCharge));
    return $this->methodCaller("InvoiceService.addRecurringOrder",$carray);
}

###public function to add a recurring order - InvoiceService.addRecurringOrder - Allows Quantity, Price and Tax###
public function addRecurringAdv($cid,$allowDup,$progId,$qty,$price,$allowTax,$merchId,$ccId,$affId,$daysToCharge) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$cid),
        php_xmlrpc_encode($allowDup),
        php_xmlrpc_encode((int)$progId),
        php_xmlrpc_encode($qty),
        php_xmlrpc_encode($price),
        php_xmlrpc_encode($allowTax),
        php_xmlrpc_encode($merchId),
        php_xmlrpc_encode((int)$ccId),
        php_xmlrpc_encode((int)$affId),
        php_xmlrpc_encode($daysToCharge));
    return $this->methodCaller("InvoiceService.addRecurringOrder",$carray);
}

###public function to get the Amount owed on an invoice - InvoiceService.calculateAmountOwed###
public function amtOwed($invId) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$invId));

    return
$this->methodCaller("InvoiceService.calculateAmountOwed",$carray);
}

###Find the id of the invoice attached to a one-time order###
public function getInvoiceId($orderId) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$orderId));

    return $this->methodCaller("InvoiceService.getInvoiceId",$carray);
}

###Find the id of an order using an invoice ID.###
public function getOrderId($invoiceId) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$invoiceId));

    return $this->methodCaller("InvoiceService.getOrderId",$carray);
}

###public function to charge invoices - InvoiceService.chargeInvoice###
public function chargeInvoice($invId,$notes,$ccId,$merchId,$bypassComm) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$invId),
        php_xmlrpc_encode($notes),
        php_xmlrpc_encode((int)$ccId),
        php_xmlrpc_encode((int)$merchId),
        php_xmlrpc_encode($bypassComm));

    return $this->methodCaller("InvoiceService.chargeInvoice",$carray);
}

###public function to create blank orders - InvoiceService.createBlankOrder###
public function blankOrder($conId,$desc,$oDate,$leadAff,$saleAff) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$conId),
        php_xmlrpc_encode($desc),
        php_xmlrpc_encode($oDate,array('auto_dates')),
        php_xmlrpc_encode((int)$leadAff),
        php_xmlrpc_encode((int)$saleAff));

    return $this->methodCaller("InvoiceService.createBlankOrder",$carray);
}

###public function to create an invioce for recurring orders - InvoiceService.createInvoiceForRecurring###
public function recurringInvoice($rid) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$rid));

    return
$this->methodCaller("InvoiceService.createInvoiceForRecurring",$carray);
}

###public function to locate creditcards based on the last 4 digits - InvoiceService.locateExistingCard###
public function locateCard($cid,$last4) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$cid),
        php_xmlrpc_encode($last4));

    return $this->methodCaller("InvoiceService.locateExistingCard",$carray);
}

###public function to Validate Credit Cards - InvoiceService.validateCreditCard###
###This public function will take a CC ID or a CC Map###
public function validateCard($ccId) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$ccId));

    return $this->methodCaller("InvoiceService.validateCreditCard",$carray);
}

###Updates the Next Bill Date on a Subscription###
public function updateSubscriptionNextBillDate($subscriptionId,$nextBillDate) {

    $carray = array(
                    php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$subscriptionId),
                    php_xmlrpc_encode($nextBillDate,array('auto_dates')));

    return
$this->methodCaller("InvoiceService.updateJobRecurringNextBillDate",$carray)
;
}

#############################
##### API EMAIL SERVICE #####
#############################

###This function will attach an email to a contacts email history###
public function attachEmail($cId, $fromName, $fromAddress, $toAddress, $ccAddresses,
                            $bccAddresses, $contentType, $subject, $htmlBody, $txtBody,
                            $header, $strRecvdDate, $strSentDate,$emailSentType=1) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$cId),
        php_xmlrpc_encode($fromName),
        php_xmlrpc_encode($fromAddress),
        php_xmlrpc_encode($toAddress),
        php_xmlrpc_encode($ccAddresses),
        php_xmlrpc_encode($bccAddresses),
        php_xmlrpc_encode($contentType),
        php_xmlrpc_encode($subject),
        php_xmlrpc_encode($htmlBody),
        php_xmlrpc_encode($txtBody),
        php_xmlrpc_encode($header),
        php_xmlrpc_encode($strRecvdDate),
        php_xmlrpc_encode($strSentDate),
        php_xmlrpc_encode($emailSentType));

    return $this->methodCaller("APIEmailService.attachEmail",$carray);
}

###This function will send an email to an array contacts###
public function sendEmail($conList, $fromAddress, $toAddress, $ccAddresses, $bccAddresses, $contentType, $subject, $htmlBody, $txtBody) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($conList),
        php_xmlrpc_encode($fromAddress),
        php_xmlrpc_encode($toAddress),
        php_xmlrpc_encode($ccAddresses),
        php_xmlrpc_encode($bccAddresses),
        php_xmlrpc_encode($contentType),
        php_xmlrpc_encode($subject),
        php_xmlrpc_encode($htmlBody),
        php_xmlrpc_encode($txtBody));

    return $this->methodCaller("APIEmailService.sendEmail",$carray);
}


###This function will send an email to an array contacts###
public function sendTemplate($conList, $template) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($conList),
        php_xmlrpc_encode($template));

    return $this->methodCaller("APIEmailService.sendEmail",$carray);
}

public function createEmailTemplate($title, $userID, $fromAddress, $toAddress, $ccAddresses, $bccAddresses, $contentType, $subject, $htmlBody,
$txtBody) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($title),
        php_xmlrpc_encode((int)$userID),
        php_xmlrpc_encode($fromAddress),
        php_xmlrpc_encode($toAddress),
        php_xmlrpc_encode($ccAddresses),
        php_xmlrpc_encode($bccAddresses),
        php_xmlrpc_encode($contentType),
        php_xmlrpc_encode($subject),
        php_xmlrpc_encode($htmlBody),
        php_xmlrpc_encode($txtBody));

    return
$this->methodCaller("APIEmailService.createEmailTemplate",$carray);
}

###Function to get an email template###
public function getEmailTemplate($templateId) {
  $carray = array(php_xmlrpc_encode($this->key),
php_xmlrpc_encode((int)$templateId));
  return $this->methodCaller("APIEmailService.getEmailTemplate",$carray);
}

/*
boolean updateEmailTemplate(
    string  key,
    int     templateId,
    string  pieceTitle,
    string  categories,
    string  fromAddress,
    string  toAddress,
    string  ccAddress,
    string  bccAddress,
    string  subject,
    string  textBody,
    string  htmlBody,
    string  contentType,
    string  mergeContext
)
*/

###Function to update an email template###
public function updateEmailTemplate($templateID, $title, $categories, $fromAddress, $toAddress, $ccAddress, $bccAddress, $subject, $textBody, $htmlBody, $contentType, $mergeContext) {
  $carray = array(php_xmlrpc_encode($this->key),
                  php_xmlrpc_encode((int)$templateID),
                  php_xmlrpc_encode($title),
                  php_xmlrpc_encode($categories),
                  php_xmlrpc_encode($fromAddress),
                  php_xmlrpc_encode($toAddress),
                  php_xmlrpc_encode($ccAddress),
                  php_xmlrpc_encode($bccAddress),
                  php_xmlrpc_encode($subject),
                  php_xmlrpc_encode($textBody),
                  php_xmlrpc_encode($htmlBody),
                  php_xmlrpc_encode($contentType),
                  php_xmlrpc_encode($mergeContext));
  return $this->methodCaller("APIEmailService.updateEmailTemplate",$carray);
}

###Function to obtain an opt status###
public function optStatus($email) {
    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($email));
    return $this->methodCaller("APIEmailService.getOptStatus", $carray); }


###Functions to opt people in/out.###
###Note that Opt-In will only work on "non-marketable contacts not opted out people.###
public function optIn($email, $reason='API Opt In') {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($email),
        php_xmlrpc_encode($reason));

    return $this->methodCaller("APIEmailService.optIn",$carray);
}

public function optOut($email, $reason='API Opt Out') {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($email),
        php_xmlrpc_encode($reason));

    return $this->methodCaller("APIEmailService.optOut",$carray);
}

////////////////////////////////////////////////////////
////////////////AFFILIATE SYSTEM FUNCTIONS////////////// ////////////////////////////////////////////////////////

###This function will return all claw backs in a date range###
public function affClawbacks($affId, $startDate, $endDate) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$affId),
        php_xmlrpc_encode($startDate),
        php_xmlrpc_encode($endDate));

    return $this->methodCaller("APIAffiliateService.affClawbacks",$carray);
}

###This function will return all commissions in a date range###
public function affCommissions($affId, $startDate, $endDate) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$affId),
        php_xmlrpc_encode($startDate,array('auto_dates')),
        php_xmlrpc_encode($endDate,array('auto_dates')));

    return
$this->methodCaller("APIAffiliateService.affCommissions",$carray);
}

###This function will return all payouts in a date range###
public function affPayouts($affId, $startDate, $endDate) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$affId),
        php_xmlrpc_encode($startDate),
        php_xmlrpc_encode($endDate));

    return $this->methodCaller("APIAffiliateService.affPayouts",$carray);
}

###Returns a list with each row representing a single affiliates totals represented by a map with key (one of the names above, and value being the total for that variable)###
public function affRunningTotals($affList) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($affList));

    return
$this->methodCaller("APIAffiliateService.affRunningTotals",$carray);
}

###This function will return how much the specified affiliates are owed###
public function affSummary($affList, $startDate, $endDate) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($affList),
        php_xmlrpc_encode($startDate),
        php_xmlrpc_encode($endDate));

    return $this->methodCaller("APIAffiliateService.affSummary",$carray);
}

////////////////////////////////////////////////////////
//////////////// TICKET SYSTEM FUNCTIONS /////////////// ////////////////////////////////////////////////////////

###This function Adds move notes to existing tickets###
public function addMoveNotes($ticketList, $moveNotes, $moveToStageId,
$notifyIds) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($ticketList),
        php_xmlrpc_encode($moveNotes),
        php_xmlrpc_encode($moveToStageId),
        php_xmlrpc_encode($notifyIds));

    return $this->methodCaller("ServiceCallService.addMoveNotes",$carray);
}

###This function Adds move notes to existing tickets###
public function moveTicketStage($ticketID, $ticketStage, $moveNotes,
$notifyIds) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$ticketID),
        php_xmlrpc_encode($ticketStage),
        php_xmlrpc_encode($moveNotes),
        php_xmlrpc_encode($notifyIds));

    return
$this->methodCaller("ServiceCallService.moveTicketStage",$carray);
}

/////////////////////////////////////////////////////////
////////////////ADDITIONAL public functionS////////////// /////////////////////////////////////////////////////////

###public function to return properly formatted dates.
public function infuDate($dateStr) {
    $dArray=date_parse($dateStr);
    if ($dArray['error_count']<1) {
        $tStamp =
mktime($dArray['hour'],$dArray['minute'],$dArray['second'],$dArray['month'],
$dArray['day'],$dArray['year']);
        return date('Ymd\TH:i:s',$tStamp);
    } else {
        foreach ($dArray['errors'] as $err) {
            echo "ERROR: " . $err . "<br />";
        }
        die("The above errors prevented the application from executing properly.");
    }
}

/////////////////////////////////////////////////////////
////////////////SearchService public functions////////////// /////////////////////////////////////////////////////////

###public function to return a saved search with all fields
public function savedSearchAllFields($savedSearchId, $userId, $page) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$savedSearchId),
        php_xmlrpc_encode((int)$userId),
        php_xmlrpc_encode((int)$page));

    return
$this->methodCaller("SearchService.getSavedSearchResultsAllFields",$carray);
}

###public function to return a saved search with selected fields
public function savedSearch($savedSearchId, $userId, $page, $fields) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$savedSearchId),
        php_xmlrpc_encode((int)$userId),
        php_xmlrpc_encode((int)$page),
        php_xmlrpc_encode($fields));

    return
$this->methodCaller("SearchService.getSavedSearchResults",$carray);
}

###public function to return the fields available in a saved report
public function getAvailableFields($savedSearchId, $userId) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$savedSearchId),
        php_xmlrpc_encode((int)$userId));

    return $this->methodCaller("SearchService.getAllReportColumns",$carray);
}

###public function to return the default quick search type for a user
public function getDefaultQuickSearch($userId) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$userId));

    return
$this->methodCaller("SearchService.getDefaultQuickSearch",$carray);
}

###public function to return the available quick search types
public function getQuickSearches($userId) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode((int)$userId));

    return
$this->methodCaller("SearchService.getAvailableQuickSearches",$carray);
}

###public function to return the results of a quick search
public function quickSearch($quickSearchType, $userId, $filterData, $page,
$limit) {

    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($quickSearchType),
        php_xmlrpc_encode((int)$userId),
        php_xmlrpc_encode($filterData),
        php_xmlrpc_encode((int)$page),
        php_xmlrpc_encode((int)$limit));

    return $this->methodCaller("SearchService.quickSearch",$carray);
}
}
?>