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
	var __ = wp.i18n.__;
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginSidebar = editor.PluginSidebar;
	var PluginSidebarMoreMenuItem = editor.PluginSidebarMoreMenuItem;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var Notice = wp.components.Notice;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;

	var PLUGIN_NAME = 'llummio-seo-fields';
	var SIDEBAR_NAME = 'llummio-seo-fields-sidebar';
	var TITLE_KEY = '_llummio_seo_title';
	var DESCRIPTION_KEY = '_llummio_seo_description';
	var TITLE_LIMIT = 160;
	var DESCRIPTION_LIMIT = 320;

	function limitValue( value, maxLength ) {
		return String( value || '' ).slice( 0, maxLength );
	}

	function getCounter( value, maxLength ) {
		return String( value || '' ).length + '/' + maxLength;
	}

	function SeoIcon() {
		return el(
			'svg',
			{
				xmlns: 'http://www.w3.org/2000/svg',
				viewBox: '0 0 24 24',
				'aria-hidden': true,
				focusable: false,
			},
			el( 'path', {
				d: 'M10.5 4a6.5 6.5 0 0 1 5.1 10.53l4.18 4.19-1.06 1.06-4.19-4.18A6.5 6.5 0 1 1 10.5 4Zm0 1.5a5 5 0 1 0 0 10 5 5 0 0 0 0-10Z',
			} )
		);
	}

	function renderSeoIcon() {
		return el( SeoIcon );
	}

	function SeoSidebar() {
		var meta = useSelect( function( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );

		var editPost = useDispatch( 'core/editor' ).editPost;
		var seoTitle = meta[ TITLE_KEY ] || '';
		var seoDescription = meta[ DESCRIPTION_KEY ] || '';

		function updateMeta( key, value ) {
			var nextMeta = Object.assign( {}, meta );
			nextMeta[ key ] = value;
			editPost( { meta: nextMeta } );
		}

		return el(
			Fragment,
			null,
			PluginSidebarMoreMenuItem &&
				el(
					PluginSidebarMoreMenuItem,
					{
						target: SIDEBAR_NAME,
						icon: renderSeoIcon(),
					},
					__( 'SEO', 'llummio-seo-fields' )
				),
			el(
				PluginSidebar,
				{
					name: SIDEBAR_NAME,
					title: __( 'SEO', 'llummio-seo-fields' ),
					icon: renderSeoIcon(),
				},
				el(
					PanelBody,
					{
						title: __( 'Search Preview Fields', 'llummio-seo-fields' ),
						initialOpen: true,
					},
					el( TextControl, {
						label: __( 'SEO Title', 'llummio-seo-fields' ),
						value: seoTitle,
						maxLength: TITLE_LIMIT,
						help: getCounter( seoTitle, TITLE_LIMIT ),
						onChange: function( value ) {
							updateMeta( TITLE_KEY, limitValue( value, TITLE_LIMIT ) );
						},
					} ),
					el( TextareaControl, {
						label: __( 'SEO Description', 'llummio-seo-fields' ),
						value: seoDescription,
						rows: 5,
						maxLength: DESCRIPTION_LIMIT,
						help: getCounter( seoDescription, DESCRIPTION_LIMIT ),
						onChange: function( value ) {
							updateMeta( DESCRIPTION_KEY, limitValue( value, DESCRIPTION_LIMIT ) );
						},
					} ),
					el(
						Notice,
						{
							status: 'info',
							isDismissible: false,
							className: 'llummio-seo-fields__notice',
						},
						__( 'Leave a field empty to use the default page value.', 'llummio-seo-fields' )
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
