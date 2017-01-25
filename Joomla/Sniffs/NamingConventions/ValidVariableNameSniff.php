<?php
/**
 * Joomla! Coding Standard
 *
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

if (class_exists('Squiz_Sniffs_NamingConventions_ValidVariableNameSniff', true) === false)
{
	throw new PHP_CodeSniffer_Exception('Class Squiz_Sniffs_NamingConventions_ValidVariableNameSniff not found');
}

/**
 * Extended ruleset for checking the naming of variables and member variables.
 *
 * @package   Joomla.CodingStandard
 * @since     1.0
 */
class Joomla_Sniffs_NamingConventions_ValidVariableNameSniff extends Squiz_Sniffs_NamingConventions_ValidVariableNameSniff
{

	/**
	 * Processes regular variables.
	 *
	 * Extends Squiz.NamingConventions.ValidVariableName.processVariable to allow underscores in calls to private member vars.
	 *
	 * @param   PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param   integer              $stackPtr  The position of the current token in the stack passed in $tokens.
	 * @return  void
	 */
	protected function processVariable(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
	{
		$tokens  = $phpcsFile->getTokens();
		$varName = ltrim($tokens[$stackPtr]['content'], '$');

		$phpReservedVars = array(
			'_SERVER',
			'_GET',
			'_POST',
			'_REQUEST',
			'_SESSION',
			'_ENV',
			'_COOKIE',
			'_FILES',
			'GLOBALS',
			'http_response_header',
			'HTTP_RAW_POST_DATA',
			'php_errormsg',
			);

		// If it's a php reserved var, then its ok.
		if (in_array($varName, $phpReservedVars) === true)
		{
			return;
		}

		$objOperator = $phpcsFile->findNext(array(T_WHITESPACE), ($stackPtr + 1), null, true);

		if ($tokens[$objOperator]['code'] === T_OBJECT_OPERATOR)
		{
			// Check to see if we are using a variable from an object.
			$var = $phpcsFile->findNext(array(T_WHITESPACE), ($objOperator + 1), null, true);

			if ($tokens[$var]['code'] === T_STRING)
			{
				return;
			}
		}

		/*
		 * There is no way for us to know if the var is public or private,
		 * so we have to ignore a leading underscore if there is one and just
		 * check the main part of the variable name.
		 */
		$originalVarName = $varName;

		if (substr($varName, 0, 1) === '_')
		{
			$objOperator = $phpcsFile->findPrevious(array(T_WHITESPACE), ($stackPtr - 1), null, true);

			if ($tokens[$objOperator]['code'] === T_DOUBLE_COLON)
			{
				// The variable lives within a class, and is referenced like
				// this: MyClass::$_variable, so we don't know its scope.
				$inClass = true;
			}
			else
			{
				$inClass = $phpcsFile->hasCondition($stackPtr, array(T_CLASS, T_INTERFACE, T_TRAIT));
			}

			if ($inClass === true)
			{
				$varName = substr($varName, 1);
			}
		}

		if (PHP_CodeSniffer::isCamelCaps($varName, false, true, false) === false)
		{
			$error = 'Variable "%s" is not in valid camel caps format';
			$data  = array($originalVarName);
			$phpcsFile->addError($error, $stackPtr, 'NotCamelCaps', $data);
		}
	}

	/**
	 * Processes class member variables.
	 *
	 * Extends Squiz.NamingConventions.ValidVariableName.processMemberVar to remove the requirement for leading underscores on
	 * private member vars.
	 *
	 * @param   PHP_CodeSniffer_File  $phpcsFile  The file being scanned.
	 * @param   integer               $stackPtr   The position of the current token in the stack passed in $tokens.
	 *
	 * @return  void
	 */
	protected function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$varName     = ltrim($tokens[$stackPtr]['content'], '$');
		$memberProps = $phpcsFile->getMemberProperties($stackPtr);

		if (empty($memberProps) === true)
		{
			// Couldn't get any info about this variable, which generally means it is invalid or possibly has a parse
			// error. Any errors will be reported by the core, so we can ignore it.
			return;
		}

		$errorData = array($varName);

		if (substr($varName, 0, 1) === '_')
		{
			$error = '%s member variable "%s" must not contain a leading underscore';
			$data  = array(
				ucfirst($memberProps['scope']),
				$errorData[0]
			);
			$phpcsFile->addError($error, $stackPtr, 'ClassVarHasUnderscore', $data);

			return;
		}

		if (PHP_CodeSniffer::isCamelCaps($varName, false, true, false) === false)
		{
			$error = 'Member variable "%s" is not in valid camel caps format';
			$phpcsFile->addError($error, $stackPtr, 'MemberNotCamelCaps', $errorData);
		}
	}
}
