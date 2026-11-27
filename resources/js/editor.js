import {
    Autoformat,
    BlockQuote,
    Bold,
    ClassicEditor,
    Essentials,
    Heading,
    Indent,
    Italic,
    Link,
    List,
    MediaEmbed,
    Paragraph,
    PasteFromOffice,
    Table,
    TableToolbar,
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';

/**
 * Turn the post body textarea into a CKEditor 5 classic editor, with the
 * toolbar the old CDN build (ckeditor5 classic 17) showed, minus image upload,
 * which never had an upload adapter. The editor writes back to the textarea
 * when the form is submitted.
 */
export function initEditor(element) {
    if (!element) {
        return;
    }

    ClassicEditor
        .create(element, {
            licenseKey: 'GPL',
            plugins: [
                Autoformat, BlockQuote, Bold, Essentials, Heading, Indent, Italic, Link, List,
                MediaEmbed, Paragraph, PasteFromOffice, Table, TableToolbar,
            ],
            toolbar: [
                'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|',
                'outdent', 'indent', '|', 'blockQuote', 'insertTable', 'mediaEmbed', 'undo', 'redo',
            ],
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
            },
        })
        .catch((error) => {
            console.error(error);
        });
}
