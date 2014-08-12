/*******************************************************************************
 * Copyright (c) 2014 IBM Corporation and other Contributors
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *     IBM - initial implementation
 *******************************************************************************/
package com.ibm.etools.mft.pattern.sen.sf.mqonewayack.code;

import com.ibm.broker.config.appdev.MessageFlow;
import com.ibm.broker.config.appdev.patterns.GeneratePatternInstanceTransform;
import com.ibm.broker.config.appdev.patterns.PatternInstanceManager;

public class PatternGenerator implements GeneratePatternInstanceTransform {

	private static final String PROJECT_NAME = "MQOnewayAckApp";
	
	private static final String PROPERTY_LOGGING_REQUIRED = "pp5";
	private static final String PROPERTY_ERROR_HANDLING_REQUIRED = "pp8";
	
	private static final String FLOW_REQUEST = "mqsi/Request.msgflow";
	private static final String FLOW_ERROR = "mqsi/Error.subflow";
	private static final String FLOW_LOG = "mqsi/Log.subflow";
	
	private static final String NODE_ERROR = "Error";
	private static final String NODE_LOG = "Log"; 
	
	@Override
	public void onGeneratePatternInstance(PatternInstanceManager patternInstanceManager) {
		
		// The location for the generated projects 
		String location = patternInstanceManager.getWorkspaceLocation();
		
		// The pattern instance name for this generation
		String patternInstanceName = patternInstanceManager.getPatternInstanceName();
		
		
		MessageFlow requestFlow = patternInstanceManager.getMessageFlow(PROJECT_NAME, FLOW_REQUEST);
		
		boolean loggingRequired = patternInstanceManager.getParameterValue(PROPERTY_LOGGING_REQUIRED).equals("true");
		boolean errorHandlingRequired = patternInstanceManager.getParameterValue(PROPERTY_ERROR_HANDLING_REQUIRED).equals("true");
		
		// Remove Log flow and node if logging is not required
		if (!loggingRequired) {
			
			if (requestFlow != null) {
				requestFlow.removeNode(requestFlow.getNodeByName(NODE_LOG));
			} 

			MessageFlow logFlow = patternInstanceManager.getMessageFlow(PROJECT_NAME, FLOW_LOG);
			patternInstanceManager.removeMessageFlow(logFlow);	
		}
		
		// Remove Error flow and node if error handling is not required
		if (!errorHandlingRequired) {

			if (requestFlow != null) {
				requestFlow.removeNode(requestFlow.getNodeByName(NODE_ERROR));
			}
			
			MessageFlow errorFlow = patternInstanceManager.getMessageFlow(PROJECT_NAME, FLOW_ERROR);
			patternInstanceManager.removeMessageFlow(errorFlow);
		}
		
	}
}
