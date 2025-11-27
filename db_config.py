import mysql.connector

def get_db_connection():
    try:
        connection = mysql.connector.connect(
            host="localhost",
            user="root",          # Default user XAMPP
            password="",          # Default password XAMPP (kosong)
            database="tiket kereta" # Sesuaikan NAMA PERSIS database kamu di phpMyAdmin
        )
        return connection
    except mysql.connector.Error as err:
        print(f"Error: {err}")
        return None

# --- Bagian ini hanya untuk mengetes koneksi ---
if __name__ == "__main__":
    conn = get_db_connection()
    if conn and conn.is_connected():
        print("✅ BERHASIL TERHUBUNG KE DATABASE!")
        conn.close()
    else:
        print("❌ GAGAL KONEK.")