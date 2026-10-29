#!/usr/bin/env bash
# Copies the runtime frontend libraries from node_modules into public/vendor.
# Run after `npm install`; the copies are committed so deployments never need Node.
set -euo pipefail

cd "$(dirname "$0")/.."
V=public/vendor
mkdir -p "$V"

copy() { # copy <src> <dest-dir>
    local src="$1" dest="$2"
    mkdir -p "$dest"
    cp -R "$src" "$dest"/
}

# Bootstrap 5
mkdir -p "$V/bootstrap/css" "$V/bootstrap/js"
cp node_modules/bootstrap/dist/css/bootstrap.min.css "$V/bootstrap/css/"
cp node_modules/bootstrap/dist/js/bootstrap.bundle.min.js "$V/bootstrap/js/"

# AdminLTE 4
mkdir -p "$V/adminlte/css" "$V/adminlte/js"
cp node_modules/admin-lte/dist/css/adminlte.min.css "$V/adminlte/css/"
cp node_modules/admin-lte/dist/js/adminlte.min.js "$V/adminlte/js/"

# Font Awesome
mkdir -p "$V/fontawesome/css"
cp node_modules/@fortawesome/fontawesome-free/css/all.min.css "$V/fontawesome/css/"
rm -rf "$V/fontawesome/webfonts"
cp -R node_modules/@fortawesome/fontawesome-free/webfonts "$V/fontawesome/"

# Bootstrap Icons
mkdir -p "$V/bootstrap-icons"
cp node_modules/bootstrap-icons/font/bootstrap-icons.min.css "$V/bootstrap-icons/"
rm -rf "$V/bootstrap-icons/fonts"
cp -R node_modules/bootstrap-icons/font/fonts "$V/bootstrap-icons/"

# Chart.js
mkdir -p "$V/chartjs"
cp node_modules/chart.js/dist/chart.umd.js "$V/chartjs/"

# SweetAlert2
mkdir -p "$V/sweetalert2"
cp node_modules/sweetalert2/dist/sweetalert2.min.css "$V/sweetalert2/"
cp node_modules/sweetalert2/dist/sweetalert2.all.min.js "$V/sweetalert2/"

# SortableJS
mkdir -p "$V/sortablejs"
cp node_modules/sortablejs/Sortable.min.js "$V/sortablejs/"

# Dropzone
mkdir -p "$V/dropzone"
cp node_modules/dropzone/dist/dropzone-min.js "$V/dropzone/"
cp node_modules/dropzone/dist/dropzone.css "$V/dropzone/"

# Swiper
mkdir -p "$V/swiper"
cp node_modules/swiper/swiper-bundle.min.css "$V/swiper/"
cp node_modules/swiper/swiper-bundle.min.js "$V/swiper/"

# Summernote
mkdir -p "$V/summernote/lang"
cp node_modules/summernote/dist/summernote-bs5.min.css "$V/summernote/"
cp node_modules/summernote/dist/summernote-bs5.min.js "$V/summernote/"
cp node_modules/summernote/dist/lang/summernote-id-ID.min.js "$V/summernote/lang/" 2>/dev/null || true
cp node_modules/summernote/dist/lang/summernote-ar-AR.min.js "$V/summernote/lang/" 2>/dev/null || true

# FullCalendar (global bundle)
mkdir -p "$V/fullcalendar"
cp node_modules/fullcalendar/index.global.min.js "$V/fullcalendar/"

# CKEditor 5 classic build
mkdir -p "$V/ckeditor"
cp node_modules/@ckeditor/ckeditor5-build-classic/build/ckeditor.js "$V/ckeditor/"

# TinyMCE (self-hosted, trimmed to the shipped plugins)
mkdir -p "$V/tinymce/models" "$V/tinymce/themes" "$V/tinymce/icons" "$V/tinymce/skins/ui" "$V/tinymce/plugins"
cp node_modules/tinymce/tinymce.min.js "$V/tinymce/"
cp -R node_modules/tinymce/models/dom "$V/tinymce/models/"
cp -R node_modules/tinymce/themes/silver "$V/tinymce/themes/"
cp -R node_modules/tinymce/icons/default "$V/tinymce/icons/"
cp -R node_modules/tinymce/skins/ui/oxide "$V/tinymce/skins/ui/"
cp -R node_modules/tinymce/skins/content "$V/tinymce/skins/" 2>/dev/null || true
for p in advlist anchor autolink charmap code emoticons fullscreen help image link lists media searchreplace table visualblocks wordcount; do
    if [ -d "node_modules/tinymce/plugins/$p" ]; then
        cp -R "node_modules/tinymce/plugins/$p" "$V/tinymce/plugins/"
    fi
done

echo "Vendored assets copied to $V"
