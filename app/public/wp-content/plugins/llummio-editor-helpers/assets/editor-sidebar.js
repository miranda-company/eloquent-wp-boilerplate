( function( wp ) {
	var editor = null;

	if ( wp ) {
		editor = wp.editor && wp.editor.PluginSidebar ? wp.editor : wp.editPost;
	}

	if (
		! wp ||
		! wp.plugins ||
		! editor ||
		! editor.PluginSidebar ||
		! wp.element ||
		! wp.components ||
		! wp.data ||
		! wp.i18n
	) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useEffect = wp.element.useEffect;
	var useState = wp.element.useState;
	var __ = wp.i18n.__;
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginSidebar = editor.PluginSidebar;
	var PanelBody = wp.components.PanelBody;
	var CheckboxControl = wp.components.CheckboxControl;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var Button = wp.components.Button;
	var Notice = wp.components.Notice;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;

	var PLUGIN_NAME = 'llummio-editor-helpers';
	var SIDEBAR_NAME = 'llummio-editor-helpers-sidebar';
	var SIDEBAR_TITLE = 'Llummio Editor Helpers';
	var TITLE_KEY = '_llummio_seo_title';
	var DESCRIPTION_KEY = '_llummio_seo_description';
	var NOINDEX_KEY = '_llummio_seo_noindex';
	var NOFOLLOW_KEY = '_llummio_seo_nofollow';
	var SCHEMA_TYPE_KEY = '_llummio_schema_type';
	var SCHEMA_NAME_KEY = '_llummio_schema_name';
	var SCHEMA_DESCRIPTION_KEY = '_llummio_schema_description';
	var SCHEMA_URL_KEY = '_llummio_schema_url';
	var SCHEMA_IMAGE_URL_KEY = '_llummio_schema_image_url';
	var SCHEMA_TELEPHONE_KEY = '_llummio_schema_telephone';
	var SCHEMA_EMAIL_KEY = '_llummio_schema_email';
	var SCHEMA_STREET_KEY = '_llummio_schema_street';
	var SCHEMA_LOCALITY_KEY = '_llummio_schema_locality';
	var SCHEMA_REGION_KEY = '_llummio_schema_region';
	var SCHEMA_POSTAL_CODE_KEY = '_llummio_schema_postal_code';
	var SCHEMA_COUNTRY_KEY = '_llummio_schema_country';
	var SCHEMA_PRICE_RANGE_KEY = '_llummio_schema_price_range';
	var SCHEMA_SERVICE_AREA_KEY = '_llummio_schema_service_area';
	var SCHEMA_PRODUCT_SKU_KEY = '_llummio_schema_product_sku';
	var SCHEMA_PRODUCT_BRAND_KEY = '_llummio_schema_product_brand';
	var SCHEMA_PRODUCT_PRICE_KEY = '_llummio_schema_product_price';
	var SCHEMA_PRODUCT_CURRENCY_KEY = '_llummio_schema_product_currency';
	var SCHEMA_PRODUCT_AVAILABILITY_KEY = '_llummio_schema_product_availability';
	var SCHEMA_FAQ_KEY = '_llummio_schema_faq_items';
	var SCHEMA_BREADCRUMB_KEY = '_llummio_schema_breadcrumb_items';
	var REVIEW_ITEM_NAME_KEY = '_llummio_schema_review_item_name';
	var REVIEW_ITEM_TYPE_KEY = '_llummio_schema_review_item_type';
	var REVIEW_RATING_KEY = '_llummio_schema_review_rating';
	var REVIEW_AUTHOR_KEY = '_llummio_schema_review_author';
	var REVIEW_BODY_KEY = '_llummio_schema_review_body';
	var TITLE_SUFFIX = ' %sep% %sitename%';
	var TITLE_RECOMMENDED_LENGTH = 60;
	var DESCRIPTION_RECOMMENDED_LENGTH = 160;
	var WIREFRAME_STORAGE_KEY = 'llummio_editor_helpers_show_wireframes';
	var WIREFRAME_COLOR_STORAGE_KEY = 'llummio_editor_helpers_wireframe_color';
	var WIREFRAME_STYLE_ID = 'llummio-editor-helpers-wireframe-style';
	var WIREFRAME_COLORS = [
		{
			label: __( 'Chroma blue', 'llummio-editor-helpers' ),
			value: '#1100ff',
		},
		{
			label: __( 'Chroma green', 'llummio-editor-helpers' ),
			value: '#26ff00',
		},
		{
			label: __( 'White', 'llummio-editor-helpers' ),
			value: '#ffffff',
		},
	];
	var DEFAULT_WIREFRAME_COLOR = WIREFRAME_COLORS[0].value;
	var SCHEMA_TYPE_OPTIONS = [
		{
			label: __( 'Default', 'llummio-editor-helpers' ),
			value: 'default',
		},
		{
			label: __( 'None', 'llummio-editor-helpers' ),
			value: 'none',
		},
		{
			label: __( 'Organization', 'llummio-editor-helpers' ),
			value: 'organization',
		},
		{
			label: __( 'Local Business', 'llummio-editor-helpers' ),
			value: 'localbusiness',
		},
		{
			label: __( 'Professional Service', 'llummio-editor-helpers' ),
			value: 'professionalservice',
		},
		{
			label: __( 'Person', 'llummio-editor-helpers' ),
			value: 'person',
		},
		{
			label: __( 'Service', 'llummio-editor-helpers' ),
			value: 'service',
		},
		{
			label: __( 'Article', 'llummio-editor-helpers' ),
			value: 'article',
		},
		{
			label: __( 'FAQ Page', 'llummio-editor-helpers' ),
			value: 'faq',
		},
		{
			label: __( 'Breadcrumb List', 'llummio-editor-helpers' ),
			value: 'breadcrumb',
		},
		{
			label: __( 'Review', 'llummio-editor-helpers' ),
			value: 'review',
		},
		{
			label: __( 'Product', 'llummio-editor-helpers' ),
			value: 'product',
		},
	];
	var REVIEW_ITEM_TYPE_OPTIONS = [
		{
			label: __( 'Product', 'llummio-editor-helpers' ),
			value: 'Product',
		},
		{
			label: __( 'Book', 'llummio-editor-helpers' ),
			value: 'Book',
		},
		{
			label: __( 'Course', 'llummio-editor-helpers' ),
			value: 'Course',
		},
		{
			label: __( 'Event', 'llummio-editor-helpers' ),
			value: 'Event',
		},
		{
			label: __( 'Movie', 'llummio-editor-helpers' ),
			value: 'Movie',
		},
		{
			label: __( 'Recipe', 'llummio-editor-helpers' ),
			value: 'Recipe',
		},
		{
			label: __( 'Software App', 'llummio-editor-helpers' ),
			value: 'SoftwareApplication',
		},
		{
			label: __( 'Local Business', 'llummio-editor-helpers' ),
			value: 'LocalBusiness',
		},
		{
			label: __( 'Professional Service', 'llummio-editor-helpers' ),
			value: 'ProfessionalService',
		},
		{
			label: __( 'Organization', 'llummio-editor-helpers' ),
			value: 'Organization',
		},
		{
			label: __( 'Person', 'llummio-editor-helpers' ),
			value: 'Person',
		},
		{
			label: __( 'Service', 'llummio-editor-helpers' ),
			value: 'Service',
		},
	];
	var PRODUCT_AVAILABILITY_OPTIONS = [
		{
			label: __( 'Not set', 'llummio-editor-helpers' ),
			value: '',
		},
		{
			label: __( 'In stock', 'llummio-editor-helpers' ),
			value: 'instock',
		},
		{
			label: __( 'Out of stock', 'llummio-editor-helpers' ),
			value: 'outofstock',
		},
		{
			label: __( 'Pre-order', 'llummio-editor-helpers' ),
			value: 'preorder',
		},
		{
			label: __( 'Back-order', 'llummio-editor-helpers' ),
			value: 'backorder',
		},
	];
	var ENTITY_SCHEMA_TYPES = [ 'organization', 'localbusiness', 'professionalservice', 'person', 'service', 'product' ];
	var BUSINESS_SCHEMA_TYPES = [ 'localbusiness', 'professionalservice' ];

	function getCounter( value, recommendedLength ) {
		return String( value || '' ).length + '/' + recommendedLength;
	}

	function getFieldClassName( value, recommendedLength ) {
		return String( value || '' ).length > recommendedLength ? 'llummio-editor-helpers__field is-over-recommended-length' : 'llummio-editor-helpers__field';
	}

	function getTitleFieldClassName( value ) {
		return getFieldClassName( value, TITLE_RECOMMENDED_LENGTH ) + ' llummio-editor-helpers__title-field';
	}

	function getPlainTextTitle( title ) {
		if ( title && typeof title === 'object' ) {
			return title.raw || title.rendered || '';
		}

		return title || '';
	}

	function getDefaultSeoTitle( postTitle ) {
		var plainTitle = String( getPlainTextTitle( postTitle ) || '' ).trim();

		return plainTitle ? plainTitle + TITLE_SUFFIX : TITLE_SUFFIX.trim();
	}

	function parseFaqItems( value ) {
		try {
			var items = JSON.parse( value || '[]' );

			return Array.isArray( items ) ? items : [];
		} catch ( error ) {
			return [];
		}
	}

	function stringifyFaqItems( items ) {
		return JSON.stringify(
			items.map( function( item ) {
				return {
					question: item.question || '',
					answer: item.answer || '',
				};
			} )
		);
	}

	function parseBreadcrumbItems( value ) {
		try {
			var items = JSON.parse( value || '[]' );

			return Array.isArray( items ) ? items : [];
		} catch ( error ) {
			return [];
		}
	}

	function stringifyBreadcrumbItems( items ) {
		return JSON.stringify(
			items.map( function( item ) {
				return {
					name: item.name || '',
					url: item.url || '',
				};
			} )
		);
	}

	function isEntitySchemaType( schemaType ) {
		return ENTITY_SCHEMA_TYPES.indexOf( schemaType ) !== -1;
	}

	function isBusinessSchemaType( schemaType ) {
		return BUSINESS_SCHEMA_TYPES.indexOf( schemaType ) !== -1;
	}

	function SeoIcon() {
		return el(
			'svg',
			{
				xmlns: 'http://www.w3.org/2000/svg',
				viewBox: '0 0 202.75 201.96',
				width: 20,
				height: 20,
				'aria-hidden': true,
				focusable: false,
			},
			el(
				'g',
				null,
				el( 'polyline', {
					fill: 'currentColor',
					points: '50.94 30.35 80.15 .03 202.75 0 202.75 121.23 172.31 151.03 172.23 30.37',
				} ),
				el( 'polyline', {
					fill: 'currentColor',
					points: '151.81 171.62 122.6 201.93 0 201.96 0 80.73 30.44 50.94 30.52 171.6',
				} )
			)
		);
	}

	function SeoSidebarHeader() {
		return el(
			'h2',
			{
				className: 'interface-complementary-area-header__title',
			},
			SIDEBAR_TITLE
		);
	}

	function renderSeoIcon() {
		return el( SeoIcon );
	}

	function getStoredWireframeState() {
		try {
			return window.localStorage.getItem( WIREFRAME_STORAGE_KEY ) === 'true';
		} catch ( error ) {
			return false;
		}
	}

	function storeWireframeState( isEnabled ) {
		try {
			window.localStorage.setItem( WIREFRAME_STORAGE_KEY, isEnabled ? 'true' : 'false' );
		} catch ( error ) {}
	}

	function isAllowedWireframeColor( color ) {
		return WIREFRAME_COLORS.some( function( option ) {
			return option.value === color;
		} );
	}

	function getStoredWireframeColor() {
		try {
			var storedColor = window.localStorage.getItem( WIREFRAME_COLOR_STORAGE_KEY );

			return isAllowedWireframeColor( storedColor ) ? storedColor : DEFAULT_WIREFRAME_COLOR;
		} catch ( error ) {
			return DEFAULT_WIREFRAME_COLOR;
		}
	}

	function storeWireframeColor( color ) {
		try {
			window.localStorage.setItem( WIREFRAME_COLOR_STORAGE_KEY, color );
		} catch ( error ) {}
	}

	function getWireframeCss( color ) {
		return '.editor-styles-wrapper .wire, .wire { border: 1px solid ' + color + ' !important; }';
	}

	function getEditorDocuments() {
		var documents = [ document ];
		var iframe = document.querySelector( 'iframe[name="editor-canvas"]' );

		if ( iframe && iframe.contentDocument ) {
			documents.push( iframe.contentDocument );
		}

		return documents;
	}

	function setWireframeStyle( isEnabled, color ) {
		getEditorDocuments().forEach( function( currentDocument ) {
			var existingStyle = currentDocument.getElementById( WIREFRAME_STYLE_ID );

			if ( ! isEnabled ) {
				if ( existingStyle ) {
					existingStyle.remove();
				}

				return;
			}

			if ( ! existingStyle && currentDocument.head ) {
				existingStyle = currentDocument.createElement( 'style' );
				existingStyle.id = WIREFRAME_STYLE_ID;
				currentDocument.head.appendChild( existingStyle );
			}

			if ( existingStyle ) {
				existingStyle.textContent = getWireframeCss( color );
			}
		} );
	}

	function SeoSidebar() {
		var wireframeState = useState( getStoredWireframeState );
		var showWireframes = wireframeState[0];
		var setShowWireframes = wireframeState[1];
		var wireframeColorState = useState( getStoredWireframeColor );
		var wireframeColor = wireframeColorState[0];
		var setWireframeColor = wireframeColorState[1];

		var editorData = useSelect( function( select ) {
			return {
				meta: select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {},
				title: select( 'core/editor' ).getEditedPostAttribute( 'title' ),
			};
		}, [] );

		var editPost = useDispatch( 'core/editor' ).editPost;
		var meta = editorData.meta;
		var seoTitle = meta[ TITLE_KEY ] || '';
		var seoDescription = meta[ DESCRIPTION_KEY ] || '';
		var noindex = !! meta[ NOINDEX_KEY ];
		var nofollow = !! meta[ NOFOLLOW_KEY ];
		var schemaType = meta[ SCHEMA_TYPE_KEY ] || 'default';
		var schemaName = meta[ SCHEMA_NAME_KEY ] || '';
		var schemaDescription = meta[ SCHEMA_DESCRIPTION_KEY ] || '';
		var schemaUrl = meta[ SCHEMA_URL_KEY ] || '';
		var schemaImageUrl = meta[ SCHEMA_IMAGE_URL_KEY ] || '';
		var schemaTelephone = meta[ SCHEMA_TELEPHONE_KEY ] || '';
		var schemaEmail = meta[ SCHEMA_EMAIL_KEY ] || '';
		var schemaStreet = meta[ SCHEMA_STREET_KEY ] || '';
		var schemaLocality = meta[ SCHEMA_LOCALITY_KEY ] || '';
		var schemaRegion = meta[ SCHEMA_REGION_KEY ] || '';
		var schemaPostalCode = meta[ SCHEMA_POSTAL_CODE_KEY ] || '';
		var schemaCountry = meta[ SCHEMA_COUNTRY_KEY ] || '';
		var schemaPriceRange = meta[ SCHEMA_PRICE_RANGE_KEY ] || '';
		var schemaServiceArea = meta[ SCHEMA_SERVICE_AREA_KEY ] || '';
		var schemaProductSku = meta[ SCHEMA_PRODUCT_SKU_KEY ] || '';
		var schemaProductBrand = meta[ SCHEMA_PRODUCT_BRAND_KEY ] || '';
		var schemaProductPrice = meta[ SCHEMA_PRODUCT_PRICE_KEY ] || '';
		var schemaProductCurrency = meta[ SCHEMA_PRODUCT_CURRENCY_KEY ] || '';
		var schemaProductAvailability = meta[ SCHEMA_PRODUCT_AVAILABILITY_KEY ] || '';
		var faqItems = parseFaqItems( meta[ SCHEMA_FAQ_KEY ] );
		var breadcrumbItems = parseBreadcrumbItems( meta[ SCHEMA_BREADCRUMB_KEY ] );
		var reviewItemName = meta[ REVIEW_ITEM_NAME_KEY ] || '';
		var reviewItemType = meta[ REVIEW_ITEM_TYPE_KEY ] || 'Product';
		var reviewRating = meta[ REVIEW_RATING_KEY ] || '';
		var reviewAuthor = meta[ REVIEW_AUTHOR_KEY ] || '';
		var reviewBody = meta[ REVIEW_BODY_KEY ] || '';
		var displayedSeoTitle = seoTitle || getDefaultSeoTitle( editorData.title );

		function updateMeta( key, value ) {
			var nextMeta = Object.assign( {}, meta );
			nextMeta[ key ] = value;
			editPost( { meta: nextMeta } );
		}

		function updateFaqItems( items ) {
			updateMeta( SCHEMA_FAQ_KEY, stringifyFaqItems( items ) );
		}

		function updateFaqItem( index, key, value ) {
			var nextItems = faqItems.slice();
			nextItems[ index ] = Object.assign( {}, nextItems[ index ] );
			nextItems[ index ][ key ] = value;
			updateFaqItems( nextItems );
		}

		function addFaqItem() {
			updateFaqItems(
				faqItems.concat( [
					{
						question: '',
						answer: '',
					},
				] )
			);
		}

		function removeFaqItem( index ) {
			updateFaqItems(
				faqItems.filter( function( item, itemIndex ) {
					return itemIndex !== index;
				} )
			);
		}

		function updateBreadcrumbItems( items ) {
			updateMeta( SCHEMA_BREADCRUMB_KEY, stringifyBreadcrumbItems( items ) );
		}

		function updateBreadcrumbItem( index, key, value ) {
			var nextItems = breadcrumbItems.slice();
			nextItems[ index ] = Object.assign( {}, nextItems[ index ] );
			nextItems[ index ][ key ] = value;
			updateBreadcrumbItems( nextItems );
		}

		function addBreadcrumbItem() {
			updateBreadcrumbItems(
				breadcrumbItems.concat( [
					{
						name: '',
						url: '',
					},
				] )
			);
		}

		function removeBreadcrumbItem( index ) {
			updateBreadcrumbItems(
				breadcrumbItems.filter( function( item, itemIndex ) {
					return itemIndex !== index;
				} )
			);
		}

		function updateWireframeState( isEnabled ) {
			setShowWireframes( isEnabled );
			storeWireframeState( isEnabled );
			setWireframeStyle( isEnabled, wireframeColor );
		}

		function updateWireframeColor( color ) {
			if ( ! isAllowedWireframeColor( color ) ) {
				return;
			}

			setWireframeColor( color );
			storeWireframeColor( color );
			setWireframeStyle( showWireframes, color );
		}

		useEffect( function() {
			setWireframeStyle( showWireframes, wireframeColor );

			var interval = window.setInterval( function() {
				setWireframeStyle( showWireframes, wireframeColor );
			}, 1000 );

			return function() {
				window.clearInterval( interval );
			};
		}, [ showWireframes, wireframeColor ] );

		return el(
			Fragment,
			null,
			el(
				PluginSidebar,
				{
					name: SIDEBAR_NAME,
					className: 'llummio-editor-helpers-sidebar',
					title: SIDEBAR_TITLE,
					icon: renderSeoIcon(),
					header: el( SeoSidebarHeader ),
				},
				el(
					PanelBody,
					{
						title: __( 'SEO Tools', 'llummio-editor-helpers' ),
						initialOpen: true,
					},
					el( TextControl, {
						label: __( 'SEO Title', 'llummio-editor-helpers' ),
						value: displayedSeoTitle,
						className: getTitleFieldClassName( displayedSeoTitle ),
						help: getCounter( displayedSeoTitle, TITLE_RECOMMENDED_LENGTH ),
						onChange: function( value ) {
							updateMeta( TITLE_KEY, value );
						},
					} ),
					el( TextareaControl, {
						label: __( 'SEO Description', 'llummio-editor-helpers' ),
						value: seoDescription,
						rows: 5,
						className: getFieldClassName( seoDescription, DESCRIPTION_RECOMMENDED_LENGTH ),
						help: getCounter( seoDescription, DESCRIPTION_RECOMMENDED_LENGTH ),
						onChange: function( value ) {
							updateMeta( DESCRIPTION_KEY, value );
						},
					} ),
					el(
						Notice,
						{
							status: 'info',
							isDismissible: false,
							className: 'llummio-editor-helpers__notice',
						},
						__( 'The title supports %sep% and %sitename%. Leave a field empty to use the default page value.', 'llummio-editor-helpers' )
					),
					el(
						'div',
						{
							className: 'llummio-editor-helpers__robots-controls',
						},
						el(
							'p',
							{
								className: 'llummio-editor-helpers__robots-label',
							},
							__( 'Robots Controls', 'llummio-editor-helpers' )
						),
						el( CheckboxControl, {
							className: 'llummio-editor-helpers__robots-control',
							label: __( 'No index', 'llummio-editor-helpers' ),
							help: __( 'Ask search engines not to show this page in search results.', 'llummio-editor-helpers' ),
							checked: noindex,
							onChange: function( isChecked ) {
								updateMeta( NOINDEX_KEY, isChecked );
							},
						} ),
						el( CheckboxControl, {
							className: 'llummio-editor-helpers__robots-control',
							label: __( 'No follow', 'llummio-editor-helpers' ),
							help: __( 'Ask search engines not to follow links on this page.', 'llummio-editor-helpers' ),
							checked: nofollow,
							onChange: function( isChecked ) {
								updateMeta( NOFOLLOW_KEY, isChecked );
							},
						} )
					)
				),
				el(
					PanelBody,
					{
						title: __( 'Schema Tools', 'llummio-editor-helpers' ),
						initialOpen: false,
					},
					el( SelectControl, {
						label: __( 'Schema Type', 'llummio-editor-helpers' ),
						value: schemaType,
						options: SCHEMA_TYPE_OPTIONS,
						onChange: function( value ) {
							updateMeta( SCHEMA_TYPE_KEY, value );
						},
					} ),
					isEntitySchemaType( schemaType ) &&
						el(
							Fragment,
							null,
							el(
								Notice,
								{
									status: 'info',
									isDismissible: false,
									className: 'llummio-editor-helpers__notice',
								},
								__( 'Only add details that are visible or clearly represented on this page.', 'llummio-editor-helpers' )
							),
							el( TextControl, {
								label: __( 'Name', 'llummio-editor-helpers' ),
								value: schemaName,
								onChange: function( value ) {
									updateMeta( SCHEMA_NAME_KEY, value );
								},
							} ),
							el( TextareaControl, {
								label: __( 'Description', 'llummio-editor-helpers' ),
								value: schemaDescription,
								rows: 4,
								onChange: function( value ) {
									updateMeta( SCHEMA_DESCRIPTION_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'URL', 'llummio-editor-helpers' ),
								value: schemaUrl,
								type: 'url',
								onChange: function( value ) {
									updateMeta( SCHEMA_URL_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Image URL', 'llummio-editor-helpers' ),
								value: schemaImageUrl,
								type: 'url',
								onChange: function( value ) {
									updateMeta( SCHEMA_IMAGE_URL_KEY, value );
								},
							} )
						),
					isBusinessSchemaType( schemaType ) &&
						el(
							Fragment,
							null,
							el( TextControl, {
								label: __( 'Telephone', 'llummio-editor-helpers' ),
								value: schemaTelephone,
								type: 'tel',
								onChange: function( value ) {
									updateMeta( SCHEMA_TELEPHONE_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Email', 'llummio-editor-helpers' ),
								value: schemaEmail,
								type: 'email',
								onChange: function( value ) {
									updateMeta( SCHEMA_EMAIL_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Street Address', 'llummio-editor-helpers' ),
								value: schemaStreet,
								onChange: function( value ) {
									updateMeta( SCHEMA_STREET_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'City', 'llummio-editor-helpers' ),
								value: schemaLocality,
								onChange: function( value ) {
									updateMeta( SCHEMA_LOCALITY_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Region', 'llummio-editor-helpers' ),
								value: schemaRegion,
								onChange: function( value ) {
									updateMeta( SCHEMA_REGION_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Postal Code', 'llummio-editor-helpers' ),
								value: schemaPostalCode,
								onChange: function( value ) {
									updateMeta( SCHEMA_POSTAL_CODE_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Country', 'llummio-editor-helpers' ),
								value: schemaCountry,
								onChange: function( value ) {
									updateMeta( SCHEMA_COUNTRY_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Price Range', 'llummio-editor-helpers' ),
								value: schemaPriceRange,
								onChange: function( value ) {
									updateMeta( SCHEMA_PRICE_RANGE_KEY, value );
								},
							} )
						),
					schemaType === 'person' &&
						el( TextControl, {
							label: __( 'Email', 'llummio-editor-helpers' ),
							value: schemaEmail,
							type: 'email',
							onChange: function( value ) {
								updateMeta( SCHEMA_EMAIL_KEY, value );
							},
						} ),
					schemaType === 'service' &&
						el( TextControl, {
							label: __( 'Area Served', 'llummio-editor-helpers' ),
							value: schemaServiceArea,
							onChange: function( value ) {
								updateMeta( SCHEMA_SERVICE_AREA_KEY, value );
							},
						} ),
					schemaType === 'product' &&
						el(
							Fragment,
							null,
							el(
								Notice,
								{
									status: 'warning',
									isDismissible: false,
									className: 'llummio-editor-helpers__notice',
								},
								__( 'Product rich results usually need a visible offer, review, or rating.', 'llummio-editor-helpers' )
							),
							el( TextControl, {
								label: __( 'SKU', 'llummio-editor-helpers' ),
								value: schemaProductSku,
								onChange: function( value ) {
									updateMeta( SCHEMA_PRODUCT_SKU_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Brand', 'llummio-editor-helpers' ),
								value: schemaProductBrand,
								onChange: function( value ) {
									updateMeta( SCHEMA_PRODUCT_BRAND_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Price', 'llummio-editor-helpers' ),
								value: schemaProductPrice,
								type: 'number',
								min: 0,
								step: 0.01,
								onChange: function( value ) {
									updateMeta( SCHEMA_PRODUCT_PRICE_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Currency', 'llummio-editor-helpers' ),
								value: schemaProductCurrency,
								help: __( 'Use a 3-letter code, for example EUR or USD.', 'llummio-editor-helpers' ),
								onChange: function( value ) {
									updateMeta( SCHEMA_PRODUCT_CURRENCY_KEY, value );
								},
							} ),
							el( SelectControl, {
								label: __( 'Availability', 'llummio-editor-helpers' ),
								value: schemaProductAvailability,
								options: PRODUCT_AVAILABILITY_OPTIONS,
								onChange: function( value ) {
									updateMeta( SCHEMA_PRODUCT_AVAILABILITY_KEY, value );
								},
							} )
						),
					schemaType === 'breadcrumb' &&
						el(
							Fragment,
							null,
							el(
								Notice,
								{
									status: 'info',
									isDismissible: false,
									className: 'llummio-editor-helpers__notice',
								},
								__( 'Leave this empty to use the page hierarchy as the breadcrumb trail.', 'llummio-editor-helpers' )
							),
							el(
								'div',
								{
									className: 'llummio-editor-helpers__repeatable-items',
								},
								breadcrumbItems.map( function( item, index ) {
									return el(
										'div',
										{
											key: index,
											className: 'llummio-editor-helpers__repeatable-item',
										},
										el( TextControl, {
											label: __( 'Breadcrumb Name', 'llummio-editor-helpers' ),
											value: item.name || '',
											onChange: function( value ) {
												updateBreadcrumbItem( index, 'name', value );
											},
										} ),
										el( TextControl, {
											label: __( 'Breadcrumb URL', 'llummio-editor-helpers' ),
											value: item.url || '',
											type: 'url',
											onChange: function( value ) {
												updateBreadcrumbItem( index, 'url', value );
											},
										} ),
										el(
											Button,
											{
												variant: 'secondary',
												isDestructive: true,
												onClick: function() {
													removeBreadcrumbItem( index );
												},
											},
											__( 'Remove Breadcrumb', 'llummio-editor-helpers' )
										)
									);
								} )
							),
							el(
								Button,
								{
									variant: 'primary',
									onClick: addBreadcrumbItem,
								},
								__( 'Add Breadcrumb', 'llummio-editor-helpers' )
							)
						),
					schemaType === 'faq' &&
						el(
							Fragment,
							null,
							el(
								Notice,
								{
									status: 'warning',
									isDismissible: false,
									className: 'llummio-editor-helpers__notice',
								},
								__( 'Only add FAQ items that are visible on this page.', 'llummio-editor-helpers' )
							),
							el(
								'div',
								{
									className: 'llummio-editor-helpers__faq-items',
								},
								faqItems.map( function( item, index ) {
									return el(
										'div',
										{
											key: index,
											className: 'llummio-editor-helpers__faq-item',
										},
										el( TextControl, {
											label: __( 'Question', 'llummio-editor-helpers' ),
											value: item.question || '',
											onChange: function( value ) {
												updateFaqItem( index, 'question', value );
											},
										} ),
										el( TextareaControl, {
											label: __( 'Answer', 'llummio-editor-helpers' ),
											value: item.answer || '',
											rows: 4,
											onChange: function( value ) {
												updateFaqItem( index, 'answer', value );
											},
										} ),
										el(
											Button,
											{
												variant: 'secondary',
												isDestructive: true,
												onClick: function() {
													removeFaqItem( index );
												},
											},
											__( 'Remove FAQ', 'llummio-editor-helpers' )
										)
									);
								} )
							),
							el(
								Button,
								{
									variant: 'primary',
									onClick: addFaqItem,
								},
								__( 'Add FAQ', 'llummio-editor-helpers' )
							)
						),
					schemaType === 'review' &&
						el(
							Fragment,
							null,
							el(
								Notice,
								{
									status: 'warning',
									isDismissible: false,
									className: 'llummio-editor-helpers__notice',
								},
								__( 'Only use review schema when the review, author, item, and rating are visible on this page. Avoid self-serving business reviews.', 'llummio-editor-helpers' )
							),
							el( TextControl, {
								label: __( 'Reviewed Item Name', 'llummio-editor-helpers' ),
								value: reviewItemName,
								onChange: function( value ) {
									updateMeta( REVIEW_ITEM_NAME_KEY, value );
								},
							} ),
							el( SelectControl, {
								label: __( 'Reviewed Item Type', 'llummio-editor-helpers' ),
								value: reviewItemType,
								options: REVIEW_ITEM_TYPE_OPTIONS,
								onChange: function( value ) {
									updateMeta( REVIEW_ITEM_TYPE_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Rating', 'llummio-editor-helpers' ),
								value: reviewRating,
								type: 'number',
								min: 1,
								max: 5,
								step: 0.1,
								help: __( 'Use a number from 1 to 5.', 'llummio-editor-helpers' ),
								onChange: function( value ) {
									updateMeta( REVIEW_RATING_KEY, value );
								},
							} ),
							el( TextControl, {
								label: __( 'Review Author', 'llummio-editor-helpers' ),
								value: reviewAuthor,
								onChange: function( value ) {
									updateMeta( REVIEW_AUTHOR_KEY, value );
								},
							} ),
							el( TextareaControl, {
								label: __( 'Review Text', 'llummio-editor-helpers' ),
								value: reviewBody,
								rows: 5,
								onChange: function( value ) {
									updateMeta( REVIEW_BODY_KEY, value );
								},
							} )
						)
				),
				el(
					PanelBody,
					{
						title: __( 'Wireframe Tools', 'llummio-editor-helpers' ),
						initialOpen: false,
					},
					el( CheckboxControl, {
						label: __( 'Show wire borders', 'llummio-editor-helpers' ),
						help: __( 'Turns .wire borders on in the editor only.', 'llummio-editor-helpers' ),
						checked: showWireframes,
						onChange: updateWireframeState,
					} ),
					el(
						'div',
						{
							className: 'llummio-editor-helpers__wire-color-control',
						},
						el(
							'p',
							{
								className: 'llummio-editor-helpers__wire-color-label',
							},
							__( 'Wire color', 'llummio-editor-helpers' )
						),
						el(
							'div',
							{
								className: 'llummio-editor-helpers__wire-color-swatches',
							},
							WIREFRAME_COLORS.map( function( option ) {
								var isSelected = option.value === wireframeColor;

								return el(
									'button',
									{
										key: option.value,
										type: 'button',
										className: isSelected ? 'llummio-editor-helpers__wire-color-swatch is-selected' : 'llummio-editor-helpers__wire-color-swatch',
										style: {
											backgroundColor: option.value,
										},
										'aria-label': option.label,
										'aria-pressed': isSelected,
										title: option.label,
										onClick: function() {
											updateWireframeColor( option.value );
										},
									},
									el(
										'span',
										{
											className: 'screen-reader-text',
										},
										option.label
									)
								);
							} )
						)
					)
				)
			)
		);
	}

	registerPlugin( PLUGIN_NAME, {
		render: SeoSidebar,
		icon: renderSeoIcon(),
	} );
} )( window.wp );
