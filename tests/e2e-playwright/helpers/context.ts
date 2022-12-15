/**
 * External dependencies
 */
import path from 'path';
import type { APIRequestContext, Browser, Page } from '@playwright/test';
import { User } from './api';
import { ADMIN } from '@e2e/factories/users';

const CONTEXT_DIR = path.resolve( __dirname, '../contexts' );

/**
 * @typedef {"admin"} UserRole
 */
/**
 * Returns the browser context by user role. E.g "admin", "student"
 *
 * @param {string} userRole
 */
export const getContextByRole = ( userRole: string ): string =>
	path.resolve( CONTEXT_DIR, `${ userRole }.json` );

export const studentRole = (): Record< string, string > => ( {
	storageState: getContextByRole( 'student' ),
} );

export const teacherRole = (): Record< string, string > => ( {
	storageState: getContextByRole( 'teacher' ),
} );

export const adminRole = (): Record< string, string > => ( {
	storageState: getContextByRole( 'admin' ),
} );

export const editorRole = (): Record< string, string > => ( {
	storageState: getContextByRole( 'editor' ),
} );

export const useAdminContext = async (
	browser: Browser
): Promise< APIRequestContext > => {
	const browserContext = await browser.newContext( adminRole() );
	return browserContext.request;
};

export const createAdminContext = async (
	page: Page
): Promise< APIRequestContext > => {
	const adminPage = await login( page, ADMIN );

	// it saves the request context
	await adminPage.request.storageState( {
		path: getContextByRole( 'admin' ),
	} );

	return page.request;
};

export const login = async ( page: Page, user: User ): Promise< Page > => {
	await page.goto( 'http://localhost:8889/wp-login.php' );
	await page.locator( 'input[name="log"]' ).fill( user.username );
	await page.locator( 'input[name="pwd"]' ).fill( user.password );
	await page.locator( 'text=Log In' ).click();
	await page.waitForNavigation();

	return page;
};
