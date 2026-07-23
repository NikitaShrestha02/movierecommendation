<form action="upload_handler.php" method="post" enctype="multipart/form-data">
    <label for="movie_id">Movie ID:</label>
    <input type="text" name="movie_id" required>
    
    <label for="poster">Select Poster Image:</label>
    <input type="file" name="poster" accept="image/*" required>
    
    <button type="submit">Upload</button>
</form>
