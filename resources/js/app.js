import './bootstrap';
import 'bootstrap';

// CKEditor is large, so it is only loaded on pages with the post body editor.
const editorElement = document.querySelector('#article-ckeditor');

if (editorElement) {
    import('./editor').then(({ initEditor }) => initEditor(editorElement));
}
