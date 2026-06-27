// Click to Chat
( function ( $ ) {
	// ready
	$( function () {
		/**
		 * todo:
		 * show:
		 * $('.ht_ctc_chat_greetings_box').show(70); / show(400); at greetings_open function
		 * $(p).show(parseInt(ctc.se)); at display function
		 * $('.for_greetings_header_image_badge').show(); at online_content function
		 * $('.ht_ctc_notification').show(400); at display_notifications function
		 * $('.ht_ctc_notification').show(400); at ht_ctc_things function
		 * hide:
		 * $('.ht_ctc_chat_greetings_box').hide(70); / hide(400); at greetings_close function
		 * $('.ht-ctc-chat .ht-ctc-cta-hover').hide(100); / hide(400); at ht_ctc_things function
		 *
		 * ht_ctc_chat_greetings_box after show/hide fix.. add as a const. while calling multiple times - cache them..
		 */

		// variables
		var v = '4.9';
		var url = window.location.href;

		var post_title = typeof document.title !== 'undefined' ? document.title : '';

		var is_mobile = 'no';
		const ht_ctc_chat = document.querySelector( '.ht-ctc-chat' );

		try {
			// Detect if the device is a mobile device based on the user agent string.
			// This covers most common mobile platforms.
			is_mobile =
				typeof navigator.userAgent !== 'undefined' &&
					navigator.userAgent.match( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i ) ?
					'yes' :
					'no';

			console.log( 'User agent: is_mobile: ' + is_mobile );
		} catch ( e ) {
			// Silently fail if navigator.userAgent is not accessible.
		}

		if ( 'no' == is_mobile ) {
			// Re-evaluate is_mobile using screen width — assume desktop if width > 1025px.
			// This ensures large-screen tablets or special browsers are classified correctly.
			is_mobile =
				typeof screen.width !== 'undefined' && screen.width > 1025 ? 'no' : 'yes';

			console.log( 'screen width: is_mobile: ' + is_mobile );
		}

		var ht_ctc_storage = {};

		// Retrieve and parse plugin-related data from localStorage and assign it to ht_ctc_storage.
		function getStorageData () {
			console.log( 'app.js - getStorageData' );

			// Check if the 'ht_ctc_storage' key exists in localStorage
			if ( localStorage.getItem( 'ht_ctc_storage' ) ) {
				// Retrieve the JSON string from localStorage
				ht_ctc_storage = localStorage.getItem( 'ht_ctc_storage' );

				// Parse the JSON string into a JavaScript object
				ht_ctc_storage = JSON.parse( ht_ctc_storage );

				console.log( ht_ctc_storage );
			}
		}
		getStorageData(); // Call the function to initialize ht_ctc_storage

		// Retrieve a specific item from the ht_ctc_storage object
		function ctc_getItem ( item ) {
			console.log( 'app.js - ctc_getItem: ' + item );

			// Return the value if the item exists, otherwise return false
			return ht_ctc_storage[ item ] ? ht_ctc_storage[ item ] : false;
		}

		// Store or update a key-value pair in ht_ctc_storage and persist it to localStorage
		function ctc_setItem ( name, value ) {
			console.log( 'app.js - ctc_setItem: name: ' + name + ' value: ' + value );

			// Refresh local copy of storage data from localStorage
			getStorageData();
			console.log( 'Storage after getStorageData():', ht_ctc_storage );

			// Update or add the item to the ht_ctc_storage object
			ht_ctc_storage[ name ] = value;
			console.log( 'Updated ht_ctc_storage:', ht_ctc_storage );

			// Convert updated storage object to a JSON string
			const newValues = JSON.stringify( ht_ctc_storage );

			// Persist the updated data to localStorage
			localStorage.setItem( 'ht_ctc_storage', newValues );
		}

		// document.dispatchEvent(
		//     new CustomEvent("ht_ctc_fn_all", { detail: { ht_ctc_storage, ctc_setItem, ctc_getItem } })
		// );

		// Initialize plugin configuration containers
		let ctc = {}; // For main chat settings
		let ctc_values = {}; // For additional configuration variables

		// Step 1: Load config from global variables if already defined (preferred and most common)
		if ( typeof ht_ctc_chat_var !== 'undefined' ) {
			ctc = ht_ctc_chat_var;
			console.log( '✅ ht_ctc_chat_var found in global scope' );
		}

		if ( typeof ht_ctc_variables !== 'undefined' ) {
			ctc_values = ht_ctc_variables;
			console.log( '✅ ht_ctc_variables found in global scope' );
		}

		// Step 2: If not available globally, fallback to fetching via REST API
		// This ensures the plugin works even when globals are not rendered inline
		if ( Object.keys( ctc ).length === 0 || Object.keys( ctc_values ).length === 0 ) {

			// Use modern async/fetch approach to get values from server
			// Once fetched, the start() function will be called internally
			// getValuesUsingRestApi();

			// existing way..
			getValues();
		} else {
			// Config already available, proceed to initialize the plugin
			start();
		}

		/**
		 * Fallback method to load settings
		 */
		function getValues () {

			console.log( 'fallback getValues' );

			if ( Object.keys( ctc ).length === 0 && document.querySelector( '.ht_ctc_chat_data' ) ) {
				try {
					const settings = document.querySelector( '.ht_ctc_chat_data' )
						?.getAttribute( 'data-settings' ) || '';
					ctc = JSON.parse( settings );
					window.ht_ctc_chat_var = ctc;
				} catch ( e ) { }
			}

			// if ctc_values is not set, then set default values
			if ( Object.keys( ctc_values ).length === 0 ) {
				ctc_values = {
					'g_an_event_name': 'click to chat',
					'pixel_event_name': 'Click to Chat by HoliThemes',
					'pixel_event_type': 'trackCustom',
					'g_an_params': [ 'g_an_param_1', 'g_an_param_2', 'g_an_param_3' ],
					'g_an_param_1': { 'key': 'number', 'value': '{number}' },
					'g_an_param_2': { 'key': 'title', 'value': '{title}' },
					'g_an_param_3': { 'key': 'url', 'value': '{url}' },
					'pixel_params': [ 'pixel_param_1', 'pixel_param_2', 'pixel_param_3', 'pixel_param_4' ],
					'pixel_param_1': { 'key': 'Category', 'value': 'Click to Chat for WhatsApp' },
					'pixel_param_2': { 'key': 'return_type', 'value': 'chat' },
					'pixel_param_3': { 'key': 'ID', 'value': '{number}' },
					'pixel_param_4': { 'key': 'Title', 'value': '{title}' },
				};

				window.ht_ctc_variables = ctc_values;
			}

			// start
			start();

		}

		/**
		 * Fallback method if wp_localize_script values are not available.
		 * Load ht_ctc_chat_var, ht_ctc_variables using REST API
		 */
		// Fetch Click to Chat settings from REST API if not already defined globally
		// function getValuesUsingRestApi() {
		// 	console.log('Loading settings from REST API');

		// 	// Extract nonce for REST API request from DOM element
		// 	let nonce = document.querySelector('.ht_ctc_chat_data')?.getAttribute('data-rest') || '';

		// 	console.log('Nonce for REST API:', nonce);

		// 	// Abort if nonce is missing, as REST API requires it for authentication
		// 	if (!nonce) {
		// 		console.warn('⛔ No nonce found for REST API. Skipping fetch calls.');
		// 		return;
		// 	}

		// 	const header = {
		// 		'X-WP-Nonce': nonce,
		// 	};

		// 	// Check if the browser supports fetch and Promise (modern environment)
		// 	if (typeof fetch !== 'undefined' && typeof Promise !== 'undefined') {
		// 		console.log('Async/fetch supported. Fetching settings from REST API...');

		// 		try {
		// 			// Asynchronously load data and then call start()
		// 			(async function () {
		// 				await load_ctc_settings(); // Fetch 'ht_ctc_chat_var' from REST API and assign to `ctc` (also saved as window.ht_ctc_chat_var)
		// 				await load_ctc_values(); // Fetch 'ht_ctc_variables from REST API and assign to `ctc_values` (also saved as window.ht_ctc_variables)
		// 				start(); // Initialize the plugin after all settings are loaded
		// 			})();
		// 		} catch (e) {
		// 			console.warn('Async fallback failed:', e);
		// 			start();
		// 		}

		// 		/**
		// 		 * Load `ht_ctc_chat_var` configuration from the REST API.
		// 		 * This includes chat button settings, position, visibility, etc.
		// 		 * The result is assigned to the local variable `ctc` and also exposed globally via `window.ht_ctc_chat_var`.
		// 		 * Called only if settings are not already available in the global scope.
		// 		 */
		// 		async function load_ctc_settings() {
		// 			try {
		// 				const controller = new AbortController();
		// 				const timeoutId = setTimeout(() => controller.abort(), 5000);

		// 				const response = await fetch(
		// 					'/wp-json/click-to-chat-for-whatsapp/v1/get_ht_ctc_chat_var',
		// 					{
		// 						method: 'GET',
		// 						signal: controller.signal,
		// 						headers: header,
		// 					}
		// 				);
		// 				clearTimeout(timeoutId);

		// 				if (response.ok) {
		// 					const data = await response.json();
		// 					if (data && typeof data === 'object') {
		// 						ctc = data;
		// 						console.log('ht_ctc_chat_var loaded:', ctc);
		// 						// Assign to global variable for easy access in other scripts
		// 						window.ht_ctc_chat_var = ctc;
		// 					}
		// 				} else {
		// 					console.warn('Failed to fetch ht_ctc_chat_var');
		// 				}
		// 			} catch (error) {
		// 				console.error('Error loading ht_ctc_chat_var:', error);
		// 			}
		// 		}

		// 		/**
		// 		 * Load `ht_ctc_variables` from the REST API.
		// 		 * These are additional global variables required for rendering or logic (e.g., online status, labels).
		// 		 * The result is assigned to the local variable `ctc_values` and also exposed globally via `window.ht_ctc_variables`.
		// 		 * Called only if values are not already available in the global scope.
		// 		 */
		// 		async function load_ctc_values() {
		// 			try {
		// 				const controller = new AbortController();
		// 				const timeoutId = setTimeout(() => controller.abort(), 5000);

		// 				const response = await fetch(
		// 					'/wp-json/click-to-chat-for-whatsapp/v1/get_ht_ctc_variables',
		// 					{
		// 						signal: controller.signal,
		// 						headers: header,
		// 					}
		// 				);
		// 				clearTimeout(timeoutId);

		// 				if (response.ok) {
		// 					const data = await response.json();
		// 					if (data && typeof data === 'object') {
		// 						ctc_values = data;
		// 						console.log('ht_ctc_variables loaded:', ctc_values);
		// 						// Assign to global variable for easy access in other scripts
		// 						window.ht_ctc_variables = ctc_values;
		// 					}
		// 				} else {
		// 					console.warn('Failed to fetch ht_ctc_variables');
		// 				}
		// 			} catch (error) {
		// 				console.error('Error loading ht_ctc_variables:', error);
		// 			}
		// 		}
		// 	} else {
		// 		// Fallback: Skip execution if the environment doesn't support fetch/Promise
		// 		console.warn('⛔ Async/fetch not supported. Skipping fetch calls.');
		// 	}
		// }

		// Initialize the plugin after settings are loaded
		function start () {
			console.log( 'start' );
			console.log( ctc );

			// remove ht_ctc_chat_data - Clean up the element after extracting settings
			var el = document.querySelector( '.ht_ctc_chat_data' );
			if ( el ) {
				el.remove();
			}

			// Dispatch a custom event to notify other scripts that plugin settings are ready
			// The event detail contains the `ctc` configuration object
			document.dispatchEvent( new CustomEvent( 'ht_ctc_event_settings', { detail: { ctc } } ) );

			// Initialize the main fixed-position chat button (bottom left or right of screen)
			ht_ctc();

			// Render any plugin shortcodes placed in the content
			shortcode();

			// Initialize any elements using the [ht-ctc] custom HTML tag or class
			custom_link();
		}

		// fixed position
		function ht_ctc () {
			console.log( 'ht_ctc' );
			if ( ht_ctc_chat ) {
				document.dispatchEvent( new CustomEvent( 'ht_ctc_event_chat' ) );

				// display
				display_settings( ht_ctc_chat );

				// click
				ht_ctc_chat.addEventListener( 'click', function () {
					// ht_ctc_chat_greetings_box (ht_ctc_chat_greetings_box_link) is not exists..

					// if greetings dialog is not exists, directly navigates to chat
					if ( ! document.querySelector( '.ht_ctc_chat_greetings_box' ) ) {
						console.log( 'no greetings dialog' );

						// link
						ht_ctc_link( ht_ctc_chat );
					}
				} );

				// greetings dialog settings..
				greetings();

				// Select the main container of the plugin to scope the click listener only to our plugin
				if ( ht_ctc_chat ) {
					// Add click event listener only within the plugin container
					ht_ctc_chat.addEventListener( 'click', function ( e ) {
						// Check if the clicked element (or its ancestor) is the greetings box link
						const target = e.target.closest( '.ht_ctc_chat_greetings_box_link' );

						if ( target ) {
							console.log( 'ht_ctc_chat_greetings_box_link' );

							// Prevent the default link behavior (like navigating away)
							e.preventDefault();

							// Get the opt-in checkbox (if it exists in DOM)
							const optCheckbox = document.querySelector( '#ctc_opt' );

							if ( optCheckbox ) {
								// Proceed only if the checkbox is checked OR user has previously opted in (via localStorage or cookie)
								if ( optCheckbox.checked || ctc_getItem( 'g_optin' ) ) {
									console.log( 'optin' );

									// Open the chat link
									ht_ctc_link( ht_ctc_chat );

									// Close the greetings box after 500ms (custom function)
									greetings_close_500();
								} else {
									// User hasn't opted in — show the opt-in prompt
									console.log( 'animate option checkbox' );

									const optInElement = document.querySelector( '.ctc_opt_in' );
									if ( optInElement ) {
										// Display the opt-in box with a fade-in effect
										optInElement.style.display = 'block';
										optInElement.style.opacity = '0';
										setTimeout( () => {
											optInElement.style.transition = 'opacity 0.4s';
											optInElement.style.opacity = '1';
										}, 10 );
									}
								}
							} else {
								// If checkbox not found, fallback to open chat directly
								ht_ctc_link( ht_ctc_chat );
								greetings_close_500();
							}

							// Dispatch a custom event so other parts of the plugin/theme can hook into this action
							document.dispatchEvent( new CustomEvent( 'ht_ctc_event_greetings' ) );
						}
					} );
				}

				// Javascript
				// Select the opt-in checkbox element
				const optCheckbox = document.querySelector( '#ctc_opt' );

				if ( optCheckbox ) {
					// Add a 'change' event listener to detect when the checkbox is checked/unchecked
					optCheckbox.addEventListener( 'change', function () {
						// Proceed only if the checkbox is checked (i.e., user opted in)
						if ( optCheckbox.checked ) {
							// Select the opt-in UI element (e.g., the popup box)
							const optInElement = document.querySelector( '.ctc_opt_in' );

							if ( optInElement ) {
								// Apply fade-out transition
								optInElement.style.transition = 'opacity 0.1s ease-out';
								optInElement.style.opacity = '0';

								// After the fade-out, hide the element completely
								setTimeout( () => {
									optInElement.style.display = 'none';
								}, 100 );
							}

							// Store the user's opt-in status using a custom utility (e.g., localStorage)
							ctc_setItem( 'g_optin', 'y' );

							// After a short delay, trigger the chat link and close the greetings box
							setTimeout( () => {
								ht_ctc_link( ht_ctc_chat );
								greetings_close_500();
							}, 500 );
						}
					} );
				}
			}
		}

		/**
		 * greetings dialog
		 */
		function greetings () {
			// Check if the main chat container exists
			if ( ht_ctc_chat ) {
				const greetingsBox = document.querySelector( '.ht_ctc_chat_greetings_box' );

				if ( greetingsBox ) {
					// Listen for clicks inside the chat container
					ht_ctc_chat.addEventListener( 'click', function ( e ) {
						// Check if the clicked element (or its parent) has `.ht_ctc_chat_style` class
						const chatStyle = e.target.closest( '.ht_ctc_chat_style' );

						if ( chatStyle ) {
							console.log( 'Greetings trigger clicked' );

							// Toggle the greetings box open/close
							if ( greetingsBox.classList.contains( 'ctc_greetings_opened' ) ) {
								console.log( 'Closing greetings box' );
								greetings_close( 'user_closed' );
							} else {
								console.log( 'Opening greetings box' );
								greetings_open( 'user_opened' );
							}
						}
					} );
				}

				// Listen for click on greetings close button
				ht_ctc_chat.addEventListener( 'click', function ( e ) {
					if ( e.target.closest( '.ctc_greetings_close_btn' ) ) {
						console.log( 'Greetings close button clicked' );
						greetings_close( 'user_closed' );
					}
				} );
			}
		}

		function greetings_display () {
			console.log( 'greetings_display' );

			const greetingsBox = document.querySelector( '.ht_ctc_chat_greetings_box' );

			if ( greetingsBox ) {
				console.log( 'greetings_display - greetings box exists' );

				// Device-specific display logic
				if ( ctc.g_device ) {
					console.log( 'greetings device based: ' + ctc.g_device );
					if ( 'yes' !== is_mobile && 'mobile' === ctc.g_device ) {
						// If device is desktop but greeting is mobile-only, remove it
						greetingsBox.remove();
						return;
					} else if ( 'yes' === is_mobile && 'desktop' === ctc.g_device ) {
						// If device is mobile but greeting is desktop-only, remove it
						greetingsBox.remove();
						return;
					}
				}

				// Dispatch custom event indicating greetings box is now displayed
				document.dispatchEvent( new CustomEvent( 'ht_ctc_event_after_chat_displayed', {
					detail: { ctc, greetings_open, greetings_close },
				} ) );

				// Auto open logic based on `g_init` config
				if ( ctc.g_init && ctc_getItem( 'g_user_action' ) !== 'user_closed' ) {
					console.log( 'g_init' );
					if ( ctc.g_init === 'default' ) {
						if ( is_mobile !== 'yes' ) {
							greetings_open( 'init' );
						}
					} else if ( ctc.g_init === 'open' ) {
						greetings_open( 'init' );
					}
				}

				// // Greetings Action: click — opens the greetings dialog when specific elements are clicked

				// // Use event delegation for dynamically added elements
				// // Listen for clicks on any element matching the selectors below
				// document.addEventListener('click', function (e) {
				//     const selector = '.ctc_greetings, #ctc_greetings, .ctc_greetings_now, [href="#ctc_greetings"]';
				//     const el = e.target.closest(selector);

				//     if (el) {
				//         console.log('greetings open triggered');

				//         e.preventDefault(); // Prevent default anchor behavior if it's a link

				//         // Close any existing greetings box first
				//         greetings_close('element');

				//         // Open the greetings box
				//         greetings_open('element');
				//     }
				// });

				// Find all elements that should trigger the greetings dialog
				// These include: .ctc_greetings, #ctc_greetings, .ctc_greetings_now, or [href="#ctc_greetings"]
				// (This is a non-delegated approach — works only for elements present at page load)

				const greetingsTriggers = document.querySelectorAll( '.ctc_greetings, #ctc_greetings, .ctc_greetings_now, [href="#ctc_greetings"]' );

				if ( greetingsTriggers.length > 0 ) {
					console.log( 'greetings open triggers found: ' + greetingsTriggers.length );

					// Attach individual click listeners to each trigger
					greetingsTriggers.forEach( function ( el ) {
						el.addEventListener( 'click', function ( e ) {
							console.log( 'greetings open triggered' );
							e.preventDefault(); // Prevent link behavior if it's an anchor

							greetings_close( 'element' ); // Close existing greetings box (if open)
							greetings_open( 'element' ); // Open greetings box
						} );
					} );
				}
			}
		}

		/**
		 * ht_ctc_chat_greetings_box_user_action - this is needed for initial close or open.. if user closed.. then no auto open initially
		 *
		 * g_action: open, close, chat_clicked, user_opened, user_closed
		 * g_user_action: user_opened, user_closed
		 *
		 *
		 * init - this is used to open greetings box on page load
		 * user_opened - this is used to track if user manually opened the greetings box
		 * user_closed - this is used to track if user manually closed the greetings box
		 *
		 */
		function greetings_open ( message = 'open' ) {
			console.log( 'Greetings open: ' + message );

			// Stop notification badge if it's currently displayed
			stop_notification_badge();

			// Remove CTA sticky button if it exists.
			// Reason: When the greetings box is shown, the CTA button can visually or functionally conflict.
			// This ensures only one interactive element is shown at a time to avoid overlapping actions.
			const el = document.querySelector( '.ht-ctc-chat .ctc_cta_stick' );
			if ( el ) {
				console.log( 'Removing sticky CTA button' );
				el.remove();
			} else {
				console.log( 'No sticky CTA button to remove' );
			}

			// Get the greetings box element
			const greetingsBox = document.querySelector( '.ht_ctc_chat_greetings_box' );
			if ( greetingsBox ) {
				// Show the greetings box with animation
				// Use shorter duration if message is 'init'
				if ( 'init' == message ) {
					$( '.ht_ctc_chat_greetings_box' )
						.show( 70 ); // jQuery animation for quick display
				} else {
					$( '.ht_ctc_chat_greetings_box' )
						.show( 400 ); // jQuery animation for standard display
				}

				// Update the state classes
				greetingsBox.classList.add( 'ctc_greetings_opened' );
				greetingsBox.classList.remove( 'ctc_greetings_closed' );
			}

			// Save user action to localStorage (via wrapper)
			ctc_setItem( 'g_action', message );
			console.log( 'g_action: ' + message );

			// If user manually opened it, also save separate user intent
			if ( 'user_opened' == message ) {
				ctc_setItem( 'g_user_action', message );
				console.log( 'g_user_action: ' + message );
			}

			// Create a modal backdrop behind the greeting box for better UX
			createModalBackdrop();
		}

		// Close the greetings box after a delay of 500 milliseconds
		function greetings_close_500 () {
			// Remove the modal backdrop behind the greetings box
			closeModalBackdrop();

			// Wait for 500 milliseconds before closing the greetings box
			setTimeout( () => {
				// Trigger the greetings close function with the action 'chat_clicked'
				greetings_close( 'chat_clicked' );
			}, 500 );
		}

		/**
		 *
		 * @param {*} message
		 */
		// Close the greetings box with different behaviors based on the message type
		function greetings_close ( message = 'close' ) {
			console.log( 'Greetings close: ' + message );

			// Remove the modal backdrop (overlay) from the screen
			closeModalBackdrop();

			// Hide the greetings box using jQuery with different durations
			if ( 'element' == message ) {
				$( '.ht_ctc_chat_greetings_box' )
					.hide( 70 ); // Quick hide for element-based close
			} else {
				$( '.ht_ctc_chat_greetings_box' )
					.hide( 400 ); // Smooth hide for standard cases
			}

			// Update the class names to reflect that the box is now closed
			const greetingsBox = document.querySelector( '.ht_ctc_chat_greetings_box' );
			if ( greetingsBox ) {
				greetingsBox.classList.add( 'ctc_greetings_closed' ); // Mark as closed
				greetingsBox.classList.remove( 'ctc_greetings_opened' ); // Remove open status
			}

			// Store the action in localStorage
			ctc_setItem( 'g_action', message );
			console.log( 'g_action: ' + message );

			// If user manually closed the greetings, store additional flag
			if ( 'user_closed' == message ) {
				ctc_setItem( 'g_user_action', message );
				console.log( 'g_user_action: ' + message );
			}
		}

		/**
		 * create modal backdrop
		 *
		 * ht_ctc_modal_open - for scroll lock by adding class to body with css overflow: hidden;
		 */
		function createModalBackdrop () {
			// Check if the modal element with .ctc_greetings_modal exists
			const modal = document.querySelector( '.ctc_greetings_modal' );
			if ( ! modal ) {
				console.log( 'No .ctc_greetings_modal found: skipping createModalBackdrop' );
				return;
			}

			console.log( 'ctc_greetings_modal exists: createModalBackdrop' );

			// Only create the backdrop if it doesn't already exist
			if ( ! document.querySelector( '.ht_ctc_modal_backdrop' ) ) {
				console.log( 'ht_ctc_modal_backdrop not found; creating .ht_ctc_modal_backdrop element' );

				const backdrop = document.createElement( 'div' );
				backdrop.className = 'ht_ctc_modal_backdrop';

				// Append the backdrop to the body
				document.body.appendChild( backdrop );

				// Add click listener to close greetings on backdrop click
				backdrop.addEventListener( 'click', function () {
					console.log( 'Backdrop clicked' );
					greetings_close( 'user_closed' );
				} );

				// Add Escape key listener with a named handler for IE-compatible removal
				function handleEscapeKey ( e ) {
					console.log( `keydown event: ${e.key}` );
					if ( e.key === 'Escape' ) {
						console.log( 'Escape key pressed' );
						greetings_close( 'user_closed' );
						document.removeEventListener( 'keydown', handleEscapeKey );
					}
				}
				document.addEventListener( 'keydown', handleEscapeKey );

				// Optionally add class to body for scroll lock or visual effects
				// document.body.classList.add('ht_ctc_modal_open');
			}
		}

		/**
		 * Close and remove the modal backdrop overlay.
		 * This is used when the greetings dialog (or any modal) is dismissed,
		 * ensuring the background overlay is also cleaned up.
		 */
		function closeModalBackdrop () {
			// Check if the modal backdrop exists in the DOM
			const modalBackdrop = document.querySelector( '.ht_ctc_modal_backdrop' );
			if ( modalBackdrop ) {
				console.log( 'ht_ctc_modal_backdrop exists: closeModalBackdrop' );

				// Remove the backdrop element from the DOM
				modalBackdrop.remove();
			}

			// Optional: remove any modal-open related styles from body
			// document.body.classList.remove('ht_ctc_modal_open');
		}

		// Display settings - handles how the chat button appears (based on schedule or directly)
		// Applies fixed-position styling and triggers content display logic
		function display_settings ( ht_ctc_chat ) {
			// If scheduling is enabled via plugin settings
			if ( ctc.schedule && 'yes' == ctc.schedule ) {
				console.log( 'scheduled' );

				// Dispatch an event so external scripts or handlers can control when/how to display
				document.dispatchEvent( new CustomEvent( 'ht_ctc_event_display', {
					detail: {
						ctc, // Chat config data
						display_chat, // Function to call when ready to display
						ht_ctc_chat, // The main chat DOM element
						online_content, // Function to update online indicators
					},
				} ) );
			} else {
				// If no schedule is applied, display the button immediately
				console.log( 'display directly' );
				display_chat( ht_ctc_chat ); // Show the button
				online_content(); // Mark badge/agent as online if needed
			}
		}

		// Determine which version of the chat button to display based on the user's device.
		// Applies positioning and styling, and ensures only the correct variant is visible.
		function display_chat ( p ) {
			if ( is_mobile == 'yes' ) {
				// If user is on mobile and mobile display is enabled
				if ( 'show' == ctc.dis_m ) {
					// Remove desktop version to avoid layout or interaction conflicts
					var rm = document.querySelector( '.ht_ctc_desktop_chat' );
					if ( rm ) { rm.remove(); }

					// Apply mobile-specific styles
					p.style.cssText = ctc.pos_m + ctc.css;

					// Show the chat element
					display( p );
				}
			} else {
				// If user is on desktop and desktop display is enabled
				if ( 'show' == ctc.dis_d ) {
					// Remove mobile version to avoid layout or interaction conflicts
					var rm = document.querySelector( '.ht_ctc_mobile_chat' );
					if ( rm ) { rm.remove(); }

					// Apply desktop-specific position and custom CSS styles
					p.style.cssText = ctc.pos_d + ctc.css;

					// Make the chat button visible
					display( p );
				}
			}
		}

		// Show the chat element using jQuery if available, else fallback to plain JS.
		// Also triggers additional plugin behavior like greetings and notifications.
		function display ( p ) {
			try {
				$( p )
					.show( parseInt( ctc.se ) );
			} catch ( e ) {
				// Fallback to basic display if jQuery is not available
				p.style.display = 'block';
			}

			// Display the greetings dialog if enabled
			greetings_display();

			// Show notification badge (e.g., unread messages or alert indicator)
			display_notifications();

			// Run any additional setup tasks or DOM adjustments for the chat element
			ht_ctc_things( p );
		}

		/**
		 * online content
		 *
		 * @since 3.34
		 */
		// This function marks the greetings header image badge as online
		function online_content () {
			console.log( 'online_content' );

			// Check if any element with class `.for_greetings_header_image_badge` exists
			if ( document.querySelector( '.for_greetings_header_image_badge' ) ) {
				// Add the `g_header_badge_online` class to all matching elements
				document.querySelectorAll( '.for_greetings_header_image_badge' )
					.forEach( ( el ) => {
						el.classList.add( 'g_header_badge_online' );
					} );

				// Use jQuery to show the badge with default animation (e.g., fadeIn)
				$( '.for_greetings_header_image_badge' )
					.show(); // Keeping jQuery for animation
			}
		}

		// Display notifications - shows the notification badge if it exists and is not stopped
		function display_notifications () {
			// Check if the notification element exists and the notification badge is not stopped
			const notificationEl = document.querySelector( '.ht_ctc_notification' );

			if ( notificationEl && ctc_getItem( 'n_badge' ) !== 'stop' ) {
				// If badge positioning element exists (for top/right override)
				const badgeEl = document.querySelector( '.ctc_nb' );

				if ( badgeEl ) {
					console.log( 'overwrite top, right' );

					// Find the closest parent with class .ht_ctc_style
					const main = badgeEl.closest( '.ht_ctc_style' );

					// Select the badge element that needs positioning
					const htCtcBadge = document.querySelector( '.ht_ctc_badge' );

					if ( main && htCtcBadge ) {
						// Get top and right values from data attributes
						const top = main.querySelector( '.ctc_nb' )
							?.getAttribute( 'data-nb_top' );
						const right = main.querySelector( '.ctc_nb' )
							?.getAttribute( 'data-nb_right' );

						// Apply the top and right styles to the badge, if defined
						if ( top !== null ) { htCtcBadge.style.top = top; }
						if ( right !== null ) { htCtcBadge.style.right = right; }
					}
				}

				// Set timeout duration based on ctc.n_time (in seconds), fallback to 150ms
				const n_time = ctc.n_time ? ctc.n_time * 1000 : 150;

				// Show the notification after the timeout with jQuery animation
				setTimeout( () => {
					console.log( 'display_notifications: show' );
					$( '.ht_ctc_notification' )
						.show( 400 ); // jQuery animation preserved
				}, n_time );
			}
		}

		// Called after the user clicks to chat or opens the greetings box
		function stop_notification_badge () {
			console.log( 'stop _notification _badge' );

			// Check if the notification element exists
			const notificationEl = document.querySelector( '.ht_ctc_notification' );

			if ( notificationEl ) {
				console.log( 'stop _notification _badge in if' );

				// Save stop flag to storage
				ctc_setItem( 'n_badge', 'stop' );

				// Remove the element from the DOM
				notificationEl.remove();
			}
		}

		// Animation and CTA hover effect
		function ht_ctc_things ( p ) {
			console.log( 'animations ' + ctc.ani );

			// Entry animation delay based on class
			var an_time = p.classList.contains( 'ht_ctc_entry_animation' ) ? 1200 : 120;

			// Add animation class after delay
			setTimeout( function () {
				p.classList.add( 'ht_ctc_animation', ctc.ani );
			}, an_time );

			// jQuery hover effect with show/hide kept exactly the same
			$( '.ht-ctc-chat' )
				.hover(
					function () {
						$( '.ht-ctc-chat .ht-ctc-cta-hover' )
							.show( 120 );
					},
					function () {
						$( '.ht-ctc-chat .ht-ctc-cta-hover' )
							.hide( 100 );
					},
				);
		}

		function ht_ctc_chat_analytics ( values ) {
			// Log the values passed for debugging
			console.log( 'analytics' );
			console.log( values );

			// Check if analytics is enabled
			if ( ctc.analytics ) {
				// If analytics is set to 'session', track only once per session
				if ( 'session' == ctc.analytics ) {
					// If already tracked in this session, skip tracking
					if ( sessionStorage.getItem( 'ht_ctc_analytics' ) ) {
						console.log( sessionStorage.getItem( 'ht_ctc_analytics' ) );
						console.log( 'no analytics' );
						return;
					} else {
						// This is a unique session
						// Set a flag in sessionStorage so analytics will not be triggered again until the browser is closed
						console.log( 'no sessionStorage' );
						sessionStorage.setItem( 'ht_ctc_analytics', 'done' );
						console.log( 'added new sessionStorage' );
					}
				}
			}

			// Function to apply dynamic values to a string containing placeholders like {number}, {title}, {url}
			function apply_variables ( v ) {
				console.log( 'apply_variables' );

				// Use chat_number if available, fallback to default number
				var number =
					ctc.chat_number && '' !== ctc.chat_number ? ctc.chat_number : ctc.number;
				console.log( number );

				try {
					console.log( v );

					// Trigger a custom event so other scripts (e.g., addon plugin, custom scripts) can hook in and modify the value
					document.dispatchEvent( new CustomEvent( 'ht_ctc_event_apply_variables', { detail: { v } } ) );

					console.log( 'window.apply_variables_value: ' + window.apply_variables_value );

					// Check if the custom event handler has modified the value and saved it to window
					v =
						typeof window.apply_variables_value !== 'undefined' ?
							window.apply_variables_value :
							v;

					console.log( v );

					// Replace template placeholders in the string with actual dynamic values:
					// {number} → WhatsApp number, {title} → Page/Post title, {url} → Current page URL
					// v = v.replace(/\{number\}/gi, number);
					v = v.replace( '{number}', number );
					v = v.replace( '{title}', post_title );
					v = v.replace( '{url}', url );
				} catch ( e ) { }

				console.log( v );
				return v;
			}

			// some unique id for the meta pixel event to avoid duplicate events
			var pixel_event_id = '';
			pixel_event_id = 'event_' + Math.floor( 10000 + Math.random() * 90000 );
			console.log( 'pixel_event_id: ' + pixel_event_id );
			ctc.ctc_pixel_event_id = pixel_event_id; // Store the unique event ID in the global variable for later use

			// Dispatch custom event to notify that analytics event has started
			document.dispatchEvent( new CustomEvent( 'ht_ctc_event_analytics' ) );

			// Get the chat number from settings or fallback
			var id = ctc.chat_number && '' !== ctc.chat_number ? ctc.chat_number : ctc.number;

			console.log( id );

			// Google Analytics setup
			/**
			 * if installed using GTM then gtag may not work. so user can create event using dataLayer object.
			 * if google anlatyics installed using gtm (from GTM user can create event using gtm datalayer object, ...)
			 *
			 * if google analytics installed directly. then gtag works.
			 *
			 * analytics - event names added to ht_ctc_chat_var (its loads most cases with out issue) and event params added to ht_ctc_variables.
			 */

			// Create basic event info
			var ga_parms = {};
			var ga_category = 'Click to Chat for WhatsApp';
			var ga_action = 'chat: ' + id;
			var ga_label = post_title + ', ' + url;

			// If GA is enabled
			if ( ctc.ga ) {
				console.log('google analytics');

				// Use custom event name or default
				var g_event_name =
					ctc.g_an_event_name && '' !== ctc.g_an_event_name ?
						ctc.g_an_event_name :
						'click to chat';
				console.log( 'Event Name: ' + g_event_name );
				g_event_name = apply_variables( g_event_name );

				// Log ctc_values for debugging
				console.log( ctc_values );

				// Build event parameters if available
				if ( ctc_values.g_an_params ) {
					console.log( 'g_an_params' );
					console.log( ctc_values.g_an_params );
					ctc_values.g_an_params.forEach( ( e ) => {
						console.log( e );
						if ( ctc_values[ e ] ) {
							var p = ctc_values[ e ];
							console.log( p );
							var k = p.key;
							var v = p.value;
							k = apply_variables( k );
							v = apply_variables( v );
							console.log( k );
							console.log( v );
							ga_parms[ k ] = v;
						}
					} );
				}
				console.log( 'ga_parms' );
				console.log( ga_parms );

				var gtag_count = 0;

				// Keep track of whether we added gtag manually
				var is_ctc_add_gtag = 'no';
				var measurement_ids = [];

				// If Google Tag Manager's dataLayer is present
				if ( typeof dataLayer !== 'undefined' ) {
					console.log( 'event with gtag id..' );

					try {
						// Define gtag function if it's not available
						if ( typeof gtag === 'undefined' ) {
							console.log( 'gtag not defined' );
							window.gtag = function () {
								dataLayer.push( arguments );
							};
							is_ctc_add_gtag = 'yes';
						}

						var tags_list = [];

						// Helper function to trigger gtag event
						function call_gtag ( tag_id ) {
							tag_id = tag_id.toUpperCase();
							console.log( 'fn: call_gtag(): ' + tag_id );

							console.log( tags_list );

							if ( tags_list.includes( tag_id ) ) {
								console.log( 'tag_id already included' );
								return;
							}

							tags_list.push( tag_id );
							console.log( tags_list );

							// Only allow certain tag ID formats
							if ( tag_id.startsWith( 'G-' ) || tag_id.startsWith( 'GT-' ) ) {
								ga_parms.send_to = tag_id;

								console.log('gtag event - send_to: ' + tag_id);
								console.log('g_event_name: ' + g_event_name);
								console.log( 'ga_parms: ' );
								console.log( ga_parms );

								gtag( 'event', g_event_name, ga_parms );

								gtag_count++;
							}
						}

						/**
						 * Helper: Add unique ID to measurement_ids array
						 */
						function addMeasurementId(id, source) {
							if (id && typeof id === 'string' && id.trim() !== '') {
								if (!measurement_ids.includes(id)) {
									console.log(`✔️ Added ${id} (from ${source})`);
									measurement_ids.push(id);
								}
							}
						}

						/**
						 * From google_tag_data.tidr.destination
						 */
						try {
							const tidr = window.google_tag_data?.tidr;
							if (tidr?.destination && typeof tidr.destination === 'object') {
								console.log('google_tag_data.tidr.destination:', tidr.destination);
								Object.keys(tidr.destination).forEach(tag_id => {
									addMeasurementId(tag_id, 'google_tag_data.destination');
								});
							}
						} catch (err) {
							console.warn('Error reading google_tag_data.tidr.destination', err);
						}

						/**
						 * From google_tag_data.tidr.container → destinations[]
						 */
						try {
							const containers = window.google_tag_data?.tidr?.container;
							if (containers && typeof containers === 'object') {
								Object.values(containers).forEach(container => {
									if (Array.isArray(container.destinations)) {
										container.destinations.forEach(dest => {
											if (typeof dest === 'string' && dest.startsWith('G-')) {
												addMeasurementId(dest, 'google_tag_data.container.destinations');
											}
										});
									}
								});
							}
						} catch (err) {
							console.warn('Error reading google_tag_data.tidr.container', err);
						}

						/**
						 * From dataLayer[] (fallback)
						 */
						try {
							if (Array.isArray(window.dataLayer)) {
								window.dataLayer.forEach(item => {
									if (Array.isArray(item) && item[0] === 'config' && typeof item[1] === 'string') {
										addMeasurementId(item[1], 'dataLayer.config');
									}
									else if (item?.send_to && typeof item.send_to === 'string') {
										addMeasurementId(item.send_to, 'dataLayer.send_to');
									}
								});
							}
						} catch (err) {
							console.warn('Error scanning dataLayer', err);
						}
						

						console.log( 'Final unique measurement_ids:: ' );
						console.log( measurement_ids );

						// Call gtag for each unique measurement ID
						measurement_ids.forEach( function ( id ) {
							call_gtag( id );
						} );
						

					} catch ( e ) { }
				}

				// Fallback: if no gtag events were sent and gtag exists, send the default event
				if (0 == gtag_count && 'no' == is_ctc_add_gtag) {
					console.log('gtag_count is 0 and gtag is not created by plugin. - sending default event');
					if ( typeof gtag !== 'undefined' ) {
						console.log('calling gtag - default (no specifc send to parm. ' + 'g_event_name: ' + g_event_name);
						console.log( 'ga_parms: ' );
						console.log( ga_parms );
						gtag( 'event', g_event_name, ga_parms );
					} else if ( typeof ga !== 'undefined' && typeof ga.getAll !== 'undefined' ) {
						console.log( 'ga' );
						var tracker = ga.getAll();
						tracker[ 0 ].send( 'event', ga_category, ga_action, ga_label );
					} else if ( typeof __gaTracker !== 'undefined' ) {
						console.log( '__gaTracker' );
						__gaTracker( 'send', 'event', ga_category, ga_action, ga_label );
					}
				}
			}

			// Push analytics event to GTM dataLayer
			if ( typeof dataLayer !== 'undefined' ) {
				console.log('dataLayer');

				// legacy
				dataLayer.push( {
					event: 'Click to Chat',
					type: 'chat',
					number: id,
					title: post_title,
					url: url,
					event_category: ga_category,
					event_label: ga_label,
					event_action: ga_action,
					ref: 'dataLayer push',
				});
				
				// new since 3.40. using admin settings.
				const pushParams = {
					...(ga_parms ?? {}),
					event: g_event_name ?? 'chat_click',
					ref: 'dataLayer push ga admin values',
				};
				dataLayer.push(pushParams);
				console.debug('dataLayer event pushed:', pushParams);
			}

			// Google Ads Conversion Tracking
			if ( ctc.ads ) {
				console.log( 'google ads enabled' );
				if ( typeof gtag_report_conversion !== 'undefined' ) {
					console.log( 'calling gtag_report_conversion' );
					gtag_report_conversion();
				}
			}

			// Facebook Pixel Tracking
			if ( ctc.fb ) {
				console.log( 'fb pixel' );

				if ( typeof fbq !== 'undefined' ) {
					// Get event name for FB Pixel or use default
					var pixelEventName =
						ctc.pixel_event_name && '' !== ctc.pixel_event_name ?
							ctc.pixel_event_name :
							'Click to Chat by HoliThemes';
					console.log( 'Event Name: ' + pixelEventName );

					// Get pixel track type: track or trackCustom
					var pixelTrack =
						ctc_values.pixel_event_type && '' !== ctc_values.pixel_event_type ?
							ctc_values.pixel_event_type :
							'trackCustom';
					console.log( 'Track: ' + pixelTrack );

					var pixelParams = {};
					console.log( typeof pixelParams );

					// Prepare pixel parameters
					if ( ctc_values.pixel_params ) {
						console.log( ctc_values.pixel_params );
						console.log( 'pixel_params' );
						ctc_values.pixel_params.forEach( ( e ) => {
							console.log( e );
							if ( ctc_values[ e ] ) {
								var p = ctc_values[ e ];
								console.log( p );
								var k = p.key;
								var v = p.value;
								k = apply_variables( k );
								v = apply_variables( v );
								console.log( k );
								console.log( v );
								pixelParams[ k ] = v;
							}
						} );
					}
					console.log( pixelParams );

					ctc.ctc_pixel_event_id = ''; // Reset the global pixel event ID

					// Send event to Facebook Pixel
					fbq(
						pixelTrack, // Usually 'track'
						pixelEventName, // e.g. 'Click to Chat by HoliThemes', 'Purchase', 'Lead'
						pixelParams, // parameters added at admin settings.  e.g. { key: value, key: 'value' }
						{
							eventID: pixel_event_id, // Deduplication key
						},
					);
				}
			}
		}

		/**
		 *  link - chat
		 * @used floating chat, shortcode, custom element. ht_ctc_chat_greetings_box_link click
		 */

		// Function to handle the click event for the chat link
		function ht_ctc_link ( values ) {
			console.log( 'ht_ctc_link' );
			console.log( values );

			console.log( ctc.number );

			// dispatch event for ctc.number
			document.dispatchEvent( new CustomEvent( 'ht_ctc_event_number', { detail: { ctc } } ) );
			console.log( ctc.number );

			var number = ctc.number;
			var pre_filled = ctc.pre_filled;

			// Check if the clicked element has a data-number attribute
			if ( values.hasAttribute( 'data-number' ) && '' !== values.getAttribute( 'data-number' ) ) {
				console.log( 'data-number is added' );
				number = values.getAttribute( 'data-number' );
				console.log( 'data-number: ' + number );
			}

			// Check if the clicked element has a data-pre_filled attribute
			if ( values.hasAttribute( 'data-pre_filled' ) ) {

				const dataPreFilled = values.getAttribute( 'data-pre_filled' ) || '';
				console.log( 'has pre_filled attribute:', dataPreFilled );

				// prefix for pre_filled text might be added.
				// const prefix = ctc.prefix_pre_filled || '';
				const prefix = ( ctc.prefix_pre_filled ) ? ctc.prefix_pre_filled : '';

				// pre_filled = prefix ? `${prefix}${dataPreFilled}` : dataPreFilled;
    			pre_filled = prefix + dataPreFilled;

				console.log( 'pre_filled:', pre_filled );
			}

			/**
			 * safari 13.. before replaceAll not supports..
			 */
			try {
				pre_filled = pre_filled.replaceAll( '%', '%25' );

				var update_url = window.location.href;
				pre_filled = pre_filled.replace( /\[url]/gi, update_url );

				// pre_filled = encodeURIComponent(pre_filled);
				pre_filled = encodeURIComponent( decodeURI( pre_filled ) );
			} catch ( e ) { }

			// if number is not defined or empty, display no number message.
			if (
				'' == number &&
				( ! ctc.custom_url_m || ctc.custom_url_m === '' ) &&
				( ! ctc.custom_url_d || ctc.custom_url_d === '' )
			) {
				console.log( 'No number and no custom URL available' );
				if ( ctc.no_number ) {
					const noNumberEl = document.querySelector( '.ctc-no-number-message' );
					if ( noNumberEl ) {
						noNumberEl.style.display = 'block';
					}
				}
				return;
			}

			// navigations links..
			// 1.base_url
			var base_url = 'https://wa.me/' + number + '?text=' + pre_filled;

			// 2.url_target - _blank, _self or if popup type just add a name - here popup only
			var url_target = ctc.url_target_d ? ctc.url_target_d : '_blank';

			if ( is_mobile == 'yes' ) {
				console.log( '-- mobile --' );

				// mobile
				if ( ctc.url_structure_m && 'wa_colon' == ctc.url_structure_m ) {
					console.log( '-- url struture: whatsapp:// --' );

					// whatsapp://.. is selected.
					base_url = 'whatsapp://send?phone=' + number + '&text=' + pre_filled;

					// for whatsapp://.. url open target is _self.
					url_target = '_self';
				}

				// mobile: own url
				if ( ctc.custom_url_m && '' !== ctc.custom_url_m ) {
					console.log( 'custom link' );
					base_url = ctc.custom_url_m;
				}
			} else {
				// desktop
				console.log( '-- desktop --' );
				if ( ctc.url_structure_d && 'web' == ctc.url_structure_d ) {
					console.log( '-- url struture: web whatsapp --' );

					// web whatsapp is enabled/selected.
					base_url =
						'https://web.whatsapp.com/send' +
						'?phone=' +
						number +
						'&text=' +
						pre_filled;
				}

				// desktop: own url
				if ( ctc.custom_url_d && '' !== ctc.custom_url_d ) {
					console.log( 'custom link' );
					base_url = ctc.custom_url_d;
				}
			}

			// 3.specs - specs - if popup then add 'pop_window_features' else 'noopener'
			var pop_window_features =
				'scrollbars=no,resizable=no,status=no,location=no,toolbar=no,menubar=no,width=788,height=514,left=100,top=100';
			var specs = 'popup' == url_target ? pop_window_features : 'noopener';
			console.log( '-- specs: ' + specs + ' --' );

			// todo: if popup is blocked by browser then it will not work. ~ so call createlink function to open link.

			// if ('popup' == url_target) {
			//     var pop_window = window.open(base_url, url_target, specs);
			//     try {
			//         // with some extensions if popup is not opened, popup focus is true - i.e. not calling cache.
			//         console.log('pop focus try..');
			//         console.log(pop_window);

			//         /**
			//          * if issue it throws error and runs cache.
			//          * (with some browser blockers it works good as the popup is loaded and it calling cache,
			//          *   but with browser extension blockers - the popup is not loaded and its not thowing cache, the code continues working.)
			//          */
			//         pop_window.focus();

			//         // for some popup blockers - .focus, .blur, .closed may not works well...  as some blockers pop_window is refering to the same window only.
			//         // if pop_window have ht_ctc_chat_var then it refer to same window. i.e. popup might be blocked. so call createlink
			//         if (pop_window.ht_ctc_chat_var) {
			//             // if true. then its not the real popup whatsapp window. some browser blockers may blocked popup
			//             console.log('ht_ctc_chat_var exists on pop_window variable');
			//             createlink();
			//         }

			//         console.log('pop window focused..');
			//     } catch (e) {
			//         console.log('pop cache');
			//         console.log(e);
			//         createlink();
			//     }
			// } else {
			//     // By adding settimeout works better with some blocker extensions.

			//     // desktop 1ms delay, mobile no settimeout
			//     if (is_mobile == 'yes') {
			//         window.open(base_url, url_target, specs);
			//     } else {
			//         setTimeout(() => {
			//             console.log('normal: window.open - with setimeout 1ms');
			//             window.open(base_url, url_target, specs);
			//         }, 1);
			//     }

			// }

			// function createlink() {
			//     console.log('createlink');
			//     var link = "<a class='ht_ctc_dynamic' style='display:none;' target='_blank' href="+ base_url +"></a>";
			//     $('body').append(link);
			//     $('.ht_ctc_dynamic')[0].click();
			//     $('.ht_ctc_dynamic').remove();
			// }

			window.open( base_url, url_target, specs );

			// Set the chat number based on the clicked element — this is the number the user is about to chat with or was navigated to
			console.log( 'chat number..: ' + number );
			ctc.chat_number = number;

			// analytics
			ht_ctc_chat_analytics( values );

			// hook
			hook( number );

			stop_notification_badge();
		}

		// shortcode
		function shortcode () {
			// shortcode - click
			$( document )
				.on( 'click', '.ht-ctc-sc-chat', function () {
				/**
				 * @since 4.3 calling ht_ctc_link function directly...
				 * benficts using global number.. page level settings number, .. random number, .. shortcode number.
				 * url structure..
				 */
					// var number = this.hasAttribute('data-number') ? this.getAttribute('data-number') : '';
					// console.log(typeof number);

					// console.log('shortcode number: ' + number);

					// if ('' == number) {
					//     console.log('shortcode: adding global number');
					//     number = ctc.number;
					//     console.log('shortcode: global number: ' + number);
					// }

					// var pre_filled = this.getAttribute('data-pre_filled');
					// pre_filled = pre_filled.replace(/\[url]/gi, url);
					// pre_filled = encodeURIComponent(pre_filled);

					// if (ctc.url_structure_d && is_mobile !== 'yes') {
					//     // web.whatsapp - if web api is enabled and is not mobile
					//     window.open('https://web.whatsapp.com/send' + '?phone=' + number + '&text=' + pre_filled, '_blank', 'noopener');
					// } else {
					//     // wa.me
					//     window.open('https://wa.me/' + number + '?text=' + pre_filled, '_blank', 'noopener');
					// }

					// // analytics
					// ctc.chat_number = number;

					// ht_ctc_chat_analytics(this);

					// // webhook
					// hook(number);

					console.log( 'shortcode click' );
					ht_ctc_link( this );
				} );
		}

		/**
		 * Initializes custom link click handlers for the Click to Chat plugin.
		 *
		 * This function sets up event listeners for elements with the classes or IDs
		 * `.ctc_chat`, `#ctc_chat`, and `[href="#ctc_chat"]`. When these elements are clicked,
		 * the `ht_ctc_link` function is called to handle the chat link functionality.
		 *
		 * If the clicked element has the class `ctc_woo_place`, the default action is prevented.
		 */
		function custom_link () {
			console.log( 'custom link' );

			// Event Delegation: handles clicks on elements that may exist now or be added later
			document.addEventListener( 'click', function ( e ) {

			    // Check if the clicked element (or its parent) matches `.ctc_chat` or `#ctc_chat`
			    const el1 = e.target.closest( '.ctc_chat, #ctc_chat' );
			    if ( el1 ) {
			        console.log( 'class/Id: ctc_chat' );

			        ht_ctc_link( el1 ); // Trigger WhatsApp action

			        // Prevent default if it's a WooCommerce-specific placement
			        if ( el1.classList.contains( 'ctc_woo_place' ) ) {
			            e.preventDefault();
			        }
			    }

			    // Check for anchor links like <a href="#ctc_chat">
			    const el2 = e.target.closest( '[href="#ctc_chat"]' );
			    if ( el2 ) {
			        console.log( 'href="#ctc_chat" clicked' );

			        e.preventDefault();    // Prevent browser jumping to #ctc_chat
			        ht_ctc_link( el2 );      // Trigger WhatsApp action
			    }
			} );

		}

		// hook related values..
		var g_hook_v = ctc.hook_v ? ctc.hook_v : '';

		// webhooks
		function hook ( number ) {
			console.log( 'hook' );

			if ( ctc.hook_url ) {
				var hook_values = {};

				// Check if the hook values are defined
				if ( ctc.hook_v ) {
					hook_values = typeof g_hook_v !== 'undefined' ? g_hook_v : ctc.hook_v;

					// var hook_values = ctc.hook_v;

					console.log( typeof hook_values );
					console.log( hook_values );

					var pair_values = {};
					var i = 1;

					// Loop through the hook values and assign them to pair_values
					hook_values.forEach( ( e ) => {
						console.log( i );
						console.log( e );
						pair_values[ 'value' + i ] = e;
						i++;
					} );

					console.log( typeof pair_values );
					console.log( pair_values );

					ctc.hook_v = pair_values;
				}

				document.dispatchEvent( new CustomEvent( 'ht_ctc_event_hook', { detail: { ctc, number } } ) );

				var h_url = ctc.hook_url;
				hook_values = ctc.hook_v;

				console.log( h_url );
				console.log( hook_values );

				// todo: if strigify and if else.. then make it like json.. make json default.
				if ( ctc.webhook_format && 'json' == ctc.webhook_format ) {
					console.log( 'main hook: json' );
					var data = hook_values;
				} else {
					console.log( 'main hook: string' );
					var data = JSON.stringify( hook_values );
				}

				console.log( data );
				console.log( typeof data );

				$.ajax( {
					url: h_url,
					type: 'POST',
					mode: 'no-cors',
					data: data,
					success: function ( response ) {
						console.log( response );
					},
				} );
			}
		}
	} );
} )( jQuery );
