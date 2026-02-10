$( document ).ready(function() {
    // Create table of contents
    $(':header').each(function() {
        tagName = $(this).prop('tagName')
        if ($(this).attr('class') == 'awlog-nolink'
            || tagName == 'H1')
            return;
        id = $(this).text().replace(/ /g, '', -1)
        $(this).attr("id", id)
        headerLevel = tagName.match(/\d/)[0]
        prefix = Array((headerLevel - 2) * 4).join('&nbsp;')
        $('#awlog-contents').append(prefix + '<a href=#' + id + '>' + $(this).text() + '</a><br>')
    })
})
