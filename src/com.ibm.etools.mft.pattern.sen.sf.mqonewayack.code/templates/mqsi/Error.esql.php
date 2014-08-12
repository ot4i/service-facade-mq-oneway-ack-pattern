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

DECLARE ErrorLoggingOn EXTERNAL BOOLEAN <?php echo ($_MB['PP']['pp8'] == 'true') ? true : false ?>;

CREATE COMPUTE MODULE SF_Build_Error_Message
	CREATE FUNCTION Main() RETURNS BOOLEAN
	BEGIN
	SET OutputRoot.Properties = NULL;
	-- No MQMD header so create domain 
	CREATE FIRSTCHILD OF OutputRoot DOMAIN ('MQMD') NAME 'MQMD';
	DECLARE MQMDRef REFERENCE TO OutputRoot.MQMD;
	SET MQMDRef.Version = MQMD_CURRENT_VERSION;
	SET MQMDRef.ApplIdentityData = SQL.BrokerName;
	SET MQMDRef.CodedCharSetId = InputRoot.Properties.CodedCharSetId;
	SET MQMDRef.Encoding = InputRoot.Properties.Encoding;

	CREATE NEXTSIBLING OF MQMDRef DOMAIN('XMLNSC') NAME 'XMLNSC';
	DECLARE OutRef REFERENCE TO OutputRoot.XMLNSC;
	-- Create error data	
	SET OutRef.Error.BrokerName = SQL.BrokerName;
	MOVE OutRef TO OutputRoot.XMLNSC.Error;
    SET OutRef.MessageFlowLabel = SQL.MessageFlowLabel; 
    SET OutRef.DTSTAMP =   CURRENT_TIMESTAMP;  

	
	
	Call AddExceptionData();
	END;
    
CREATE PROCEDURE AddExceptionData() BEGIN
	
		DECLARE ERef REFERENCE TO OutputRoot.XMLNSC.Error; 
	    -- Add some exception data for error and fault
		DECLARE Error INTEGER;
		DECLARE Text CHARACTER;
		DECLARE Label CHARACTER;
		Declare FaultText CHARACTER 'Exception data:';
		DECLARE I INTEGER 1;
		DECLARE K INTEGER;
		DECLARE start REFERENCE TO InputExceptionList.*[1];

		WHILE start.Number IS NOT NULL DO 
			SET Label = start.Label;
			SET Error = start.Number;
			IF Error = 3001 THEN
				SET Text = start.Insert.Text;
			ELSE
				SET Text = start.Text;
			END IF;
			-- Don't include the "Caught exception and rethrowing message"
			IF Error <> 2230 THEN
				-- Process inserts
				DECLARE Inserts Character;
				DECLARE INS Integer;
				Set Inserts = '';
				-- Are there any inserts for this exception
				IF EXISTS (start.Insert[]) THEN
					-- If YES add them to inserts string
				 	SET Inserts = Inserts || COALESCE(start.Insert[1].Text,'NULL')|| ' / ';
				 	SET K = 1;
				 	INSERTS: LOOP
						IF CARDINALITY(start.Insert[])> K 
						THEN 
							SET Inserts = Inserts || COALESCE(start.Insert[K+1].Text,'NULL')|| ' / ';
						-- No more inserts to process
						ELSE LEAVE INSERTS;
						END IF;
					SET K = K+1;
					END LOOP INSERTS;
				END IF;
				SET ERef.Exception[I].Label = Label;
				SET ERef.Exception[I].Error = Error;
				SET ERef.Exception[I].Text = Text;
				Set ERef.Exception[I].Inserts = COALESCE(Inserts, '');
				
				SET FaultText = FaultText || ' Label: ' || COALESCE(Label, ''); 
				SET FaultText = FaultText || ' Error: ' || COALESCE(CAST(Error AS CHARACTER), '');
				SET FaultText = FaultText || ' Text: ' || COALESCE(Text, '');
				SET FaultText = FaultText || ' Inserts: ' || COALESCE(Inserts, '');
			
				SET I = I+1; 
			END IF;
			-- Move start to the last child of the field to which it currently points
			MOVE start LASTCHILD;
		END WHILE;
				SET Environment.PatternVariables.FaultText = FaultText;
	END;    
    
 
END MODULE;

CREATE FILTER MODULE CheckifMessageSent
	CREATE FUNCTION Main() RETURNS BOOLEAN
	BEGIN
		RETURN Environment.PatternVariables.Complete = 'Complete';

	END;
	END MODULE;
CREATE FILTER MODULE CheckErrorLogging
	CREATE FUNCTION Main() RETURNS BOOLEAN
	BEGIN
		RETURN ErrorLoggingOn;
	END;
	END MODULE;
	
	
CREATE DATABASE MODULE Throw
	CREATE FUNCTION Main() RETURNS BOOLEAN
	BEGIN
	THROW USER EXCEPTION SEVERITY 3 MESSAGE 2372 VALUES(Environment.PatternVariables.FaultText);
END;
END MODULE;