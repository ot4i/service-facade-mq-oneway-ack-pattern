BROKER SCHEMA mqsi

/**
 * Copyright (c) 2014 IBM Corporation and other Contributors
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *     IBM - initial implementation
**/

DECLARE LoggingOn EXTERNAL BOOLEAN <?php echo ($_MB['PP']['pp5'] == 'true') ? true : false ?>;

CREATE COMPUTE MODULE Build_SOAP_Reply_Ack
	CREATE FUNCTION Main() RETURNS BOOLEAN
	BEGIN
/*********************************************************
*  service name space extracted and set as a derived POV *
**********************************************************/
		DECLARE ServiceNamespace  CHARACTER 'http://com.http.addressbook';
		SET Environment.PatternVariables.Complete = 'Complete';
		SET OutputRoot.XMLNSC.{ServiceNamespace}:Response.Acknowledgement = 'Accepted';
		RETURN TRUE;
	END;
END MODULE;

CREATE COMPUTE MODULE STRIP
	CREATE FUNCTION Main() RETURNS BOOLEAN
	BEGIN

/*********************************************************
*  Remove HTTP header and SOAP envelope                  *
**********************************************************/


	SET OutputRoot.Properties = NULL;
	-- No MQMD header so create domain 
	CREATE FIRSTCHILD OF OutputRoot DOMAIN ('MQMD') NAME 'MQMD';
	DECLARE MQMDRef REFERENCE TO OutputRoot.MQMD;
	SET MQMDRef.Version = MQMD_CURRENT_VERSION;
	SET MQMDRef.CodedCharSetId = InputRoot.Properties.CodedCharSetId;
	SET MQMDRef.Encoding = InputRoot.Properties.Encoding;

	CREATE NEXTSIBLING OF MQMDRef DOMAIN('XMLNSC') NAME 'XMLNSC';
	SET OutputRoot.XMLNSC.* = InputRoot.*:SOAP.*:Body.*[1];

		RETURN TRUE;
	END;
END MODULE;