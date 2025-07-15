
jQuery(document).ready(function($) {

    // Function to correct rendering of date picker field
    function fixDatePicker() {
        var dateInput = $('input[name="work_notes_date"]');
        dateInput.focus();
        dateInput.blur();
    }

    // Bind all event handlers (edit, delete) for dynamic content
    function bindWorkNotesEvents() {

        // Handle editing an existing note
        $(document).on('click', '.edit-note', function () {
            console.log("Setting note_id to", noteId);
            console.log("note_id input exists?", $('input[name="note_id"]').length);

            var noteId = $(this).data('note-id');
            var wpid = $('input[name="wpid"]').val();

            var data = {
                action: 'load_work_note',
                nonce: mainwpWorkNotes.nonce,
                wpid: wpid,
                note_id: noteId
            };

            $.post(mainwpWorkNotes.ajax_url, data, function (response) {
                if (response.success) {
                    // Populate form with existing note data
                    $('input[name="note_id"]').val(noteId);
                    $('input[name="work_notes_date"]').val(response.data.date);

                    // Set TinyMCE content or fallback to textarea
                    var editor = tinyMCE.get('work_notes_content');
                    if (editor && editor instanceof tinymce.Editor) {
                        editor.setContent(response.data.content);
                    } else {
                        $('textarea[name="work_notes_content"]').val(response.data.content);
                    }

                    fixDatePicker();
                } else {
                    alert(response.data.message);
                }
            });
        });

        // Handle deleting a note
        $('.delete-note').on('click', function () {
            var noteId = $(this).data('note-id');
            var wpid = $('input[name="wpid"]').val();

            var data = {
                action: 'delete_work_note',
                nonce: mainwpWorkNotes.nonce,
                wpid: wpid,
                note_id: noteId
            };

            $.post(mainwpWorkNotes.ajax_url, data, function (response) {
                if (response.success) {
                    alert('Note deleted successfully!');
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        });
    }

    // Initialize all field behaviors on first load
    fixDatePicker();
    bindWorkNotesEvents();
    console.log("V1.01");

    // Handle saving the note (new or edited)
    $('#save-work-note').on('click', function () {
        var data = {
            action: 'save_work_note',
            nonce: mainwpWorkNotes.nonce,
            wpid: $('input[name="wpid"]').val(),
            note_id: $('input[name="note_id"]').val(),  // This must be set properly when editing
            work_notes_date: $('input[name="work_notes_date"]').val(),
            work_notes_content: tinyMCE.get('work_notes_content') ? tinyMCE.get('work_notes_content').getContent() : $('textarea[name="work_notes_content"]').val()
        };

        console.log("Submitting note ID:", data.note_id);  // Debugging output

        $.post(mainwpWorkNotes.ajax_url, data, function (response) {
            if (response.success) {
                alert('Note saved successfully!');
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    });

    // Rebind events after AJAX loads the notes tab
    $('#mainwp_tab_WorkNotes').on('click', function (e) {
        e.preventDefault();
        var siteId = $(this).data('siteid');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'load_work_notes_form',
                site_id: siteId
            },
            success: function (response) {
                $('#mainwp_tab_WorkNotes_container').html(response);
                bindWorkNotesEvents();  // Rebind after DOM replacement
            }
        });
    });

});
