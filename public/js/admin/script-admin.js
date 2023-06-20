document.addEventListener('DOMContentLoaded', function() {
    setHrefs('/admin/podcast/edit/', 'a.edit-podcast')
    setHrefs('/admin/podcast/remove/', 'a.delete-podcast')  
})

function setHrefs($new_href, $selector) {
    var ul = document.getElementById('songs');
    var links = ul.querySelectorAll($selector);

    for (var i = 0; i < links.length; i++) {
        var link = links[i];
        var parts = link.href.split("/");
        var id = parts[parts.length - 1];
        link.href = $new_href + id;
    }
}