( function() {
    CKEDITOR.plugins.add( 'mediaembed',
    {
        init: function( editor )
        {
           var me = this;
           CKEDITOR.dialog.add( 'MediaEmbedDialog', function( editor ) {
            return {
              title : 'Embed Media Dialog',
              minWidth : 400,
              minHeight : 200,
              contents : [
                {
					        id : 'mediaTab',
					        label : 'Embed media code',
					        title : 'Embed media code',
					        elements :
					        [
					          {
					            id : 'embed',
					            type : 'textarea',
					            label : 'Paste embed code here'
					          }
					        ]
                }
              ],
              onOk : function() {
                var editor = this.getParentEditor();
                var content = this.getValueOf( 'mediaTab', 'embed' );
                if ( content.length>0 ) {
                  editor.insertHtml('<div class="media_embed">'+content+'</div>');
                }
              }
            };
           });

            editor.addCommand( 'MediaEmbed', new CKEDITOR.dialogCommand( 'MediaEmbedDialog' ) );

            editor.ui.addButton( 'MediaEmbed',
            {
                label: 'Embed Media',
                command: 'MediaEmbed',
                icon: this.path + 'images/icon.png'
            } );
        }
    } );
} )();
