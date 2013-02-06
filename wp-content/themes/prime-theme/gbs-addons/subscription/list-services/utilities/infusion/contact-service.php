<?php
###########################################################################################################
###Sample Created by Justin Morris on 7/8/08                                                            ###
###In this sample we create a script that will allow forms to post to it and then                       ###
###take the posted data and create a contact in Infusionsoft and add it to a group and/or campaign.     ###
###The forms.html file included with this sample is the page that posts to this script.                 ###
###########################################################################################################

###Include our XMLRPC Library###
include("xmlrpc-2.0/lib/xmlrpc.inc");

###Set our Infusionsoft application as the client###
$client = new xmlrpc_client("https://mach2.infusionsoft.com/api/xmlrpc");

###Return Raw PHP Types###
$client->return_type = "phpvals";

###Dont bother with certificate verification###
$client->setSSLVerifyPeer(FALSE);


###########################################
###Our Function to add people to a group###
###########################################
function addGrp($CID, $GID) {
###Set up global variables###
	global $client;
	
	
	###Our API Key###
	$key = get_option(Group_Buying_Infusionsoft::APPKEY);
	
	
###Set up the call to add to the group###
	$call = new xmlrpcmsg("ContactService.addToGroup", array(
		php_xmlrpc_encode($key), 		#The encrypted API key
		php_xmlrpc_encode($CID),		#The contact ID
		php_xmlrpc_encode($GID),		#The Group ID
	));
###Send the call###
	$result = $client->send($call);

	if(!$result->faultCode()) {
		print "Contact added to group " . $GID;
		print "<BR>";
	} else {
		print $result->faultCode() . "<BR>";
		print $result->faultString() . "<BR>";
	}
}

##############################################
###Our Function to add people to a campaign###
##############################################
function addCamp($CID, $CMP) {
###Set up global variables###
	global $client;
	
	###Our API Key###
	$key = get_option(Group_Buying_Infusionsoft::APPKEY);
	
	
###Set up the call to add to the campaign###
	$call = new xmlrpcmsg("ContactService.addToCampaign", array(
		php_xmlrpc_encode($key), 		#The encrypted API key
		php_xmlrpc_encode($CID),		#The contact ID
		php_xmlrpc_encode($CMP),		#The Campaign ID
	));
###Send the call###
	$result = $client->send($call);

	if(!$result->faultCode()) {
		print "Contact added to Campaign " . $CMP;
		print "<BR>";
	} else {
		print $result->faultCode() . "<BR>";
		print $result->faultString() . "<BR>";
	}
}
