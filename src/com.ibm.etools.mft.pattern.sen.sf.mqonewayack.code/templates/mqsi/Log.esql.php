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

-- Check if logging has been switched off with UDP
CREATE FILTER MODULE CheckLogging
	CREATE FUNCTION main() RETURNS BOOLEAN BEGIN
	
	RETURN LoggingOn;
	END;

END MODULE;

CREATE Compute MODULE CreateLogMessage

	CREATE FUNCTION main() RETURNS BOOLEAN BEGIN
-- No MQMD header so create domain and then add MQRFH2
	SET OutputRoot.Properties = NULL;
	CREATE FIRSTCHILD OF OutputRoot DOMAIN ('MQMD') NAME 'MQMD';
	DECLARE MQMDRef REFERENCE TO OutputRoot.MQMD;
	SET MQMDRef.Format = MQFMT_RF_HEADER_2;
	SET MQMDRef.Version = MQMD_CURRENT_VERSION;
	SET MQMDRef.CodedCharSetId = InputRoot.Properties.CodedCharSetId;
	SET MQMDRef.Encoding = InputRoot.Properties.Encoding;
	CREATE NEXTSIBLING OF MQMDRef DOMAIN('MQRFH2') NAME 'MQRFH2';

-- Create Log data
	SET OutputRoot.MQRFH2.(MQRFH2.Field)Version = 2;
	SET OutputRoot.MQRFH2.usr.BrokerName = SQL.BrokerName;
	DECLARE OutRef REFERENCE TO OutputRoot.MQRFH2.usr;
    SET OutRef.MessageFlowLabel = SQL.MessageFlowLabel; 
    SET OutRef.DTSTAMP =   CURRENT_TIMESTAMP;
    -- Add service information if this is the request leg    
    IF Environment.PatternVariables.RRMode = 'Request'THEN
    SET OutRef.SOAPVersion = InputRoot.SOAP.Context.SOAP_Version;
    SET OutRef.ServiceName = InputRoot.SOAP.Context.service;
    SET OutRef.ServiceNameSpace = InputRoot.SOAP.Context.Namespace;
    SET OutRef.ServicePort = InputRoot.SOAP.Context.port;
    SET OutRef.ServicePortType = InputRoot.SOAP.Context.portType;
    SET OutRef.ServiceOperation = InputRoot.SOAP.Context.operation;
	END IF;
	CREATE NEXTSIBLING OF OutRef DOMAIN('XMLNSC') NAME 'XMLNSC';
	SET OutputRoot.XMLNSC.Message = InputBody;
END;
END MODULE;


CREATE Compute MODULE SF_CreateTraceData
CREATE FUNCTION main() RETURNS BOOLEAN BEGIN
	DECLARE EnvVarRef REFERENCE TO Environment.PatternVariables;
	SET EnvVarRef.DTSTAMP = CURRENT_TIMESTAMP; 
	SET EnvVarRef.BrokerName = SQL.BrokerName;
    SET EnvVarRef.MessageFlowlabel = SQL.MessageFlowLabel;
    SET EnvVarRef.Action = InputRoot.SOAP.Context.operation;
   	SET EnvVarRef.Text = 'Error writing to the log queue, in the Service Facade to WebSphere MQ: one-way with acknowledgement pattern';
END;
END MODULE;