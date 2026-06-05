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
	var Notice = wp.components.Notice;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;

	var PLUGIN_NAME = 'llummio-editor-helpers';
	var SIDEBAR_NAME = 'llummio-editor-helpers-sidebar';
	var SIDEBAR_TITLE = 'Llummio Editor Helpers';
	var TITLE_KEY = '_llummio_seo_title';
	var DESCRIPTION_KEY = '_llummio_seo_description';
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
		var displayedSeoTitle = seoTitle || getDefaultSeoTitle( editorData.title );

		function updateMeta( key, value ) {
			var nextMeta = Object.assign( {}, meta );
			nextMeta[ key ] = value;
			editPost( { meta: nextMeta } );
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
