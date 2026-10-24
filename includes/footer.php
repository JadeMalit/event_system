</div> <!-- End Container -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php if(isset($_GET['status'])): ?>
    <script>
        <?php if($_GET['status'] == 'success'): ?>
            Swal.fire({
                title: 'Registered Successfully!',
                text: 'Your registration has been recorded.',
                icon: 'success',
                confirmButtonColor: '#4f46e5'
            });
        <?php elseif($_GET['status'] == 'duplicate'): ?>
            Swal.fire({
                title: 'Already Registered!',
                text: 'You have already registered for this event using this Student ID.',
                icon: 'warning',
                confirmButtonColor: '#4f46e5'
            });
        <?php endif; ?>
    </script>
<?php endif; ?>

</body>
</html>