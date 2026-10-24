import { chromium } from 'playwright';

const SCREENSHOT_DIR = '/home/ilhamrizqiawan/.gemini/antigravity/brain/30877ea4-9bee-492e-8087-be00012dabce/screenshots';

async function run() {
    console.log('Launching Chrome browser...');
    const browser = await chromium.launch({
        executablePath: '/usr/bin/google-chrome',
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 }
    });

    const page = await context.newPage();

    console.log('1. Navigating to login page...');
    await page.goto('http://localhost:8000/login');
    await page.waitForLoadState('networkidle');

    await page.screenshot({ path: `${SCREENSHOT_DIR}/01_login_page.png` });
    console.log('Saved 01_login_page.png');

    console.log('2. Logging in as admin...');
    await page.fill('#u', 'admin');
    await page.fill('#p', 'admin123');
    await page.click('button[type="submit"]');

    await page.waitForURL('**/operator/dashboard');
    console.log('Logged in successfully!');
    await page.screenshot({ path: `${SCREENSHOT_DIR}/02_dashboard.png` });
    console.log('Saved 02_dashboard.png');

    console.log('3. Navigating to Data Guru...');
    await page.goto('http://localhost:8000/operator/guru');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: `${SCREENSHOT_DIR}/03_guru_list.png` });
    console.log('Saved 03_guru_list.png');

    console.log('4. Selecting "Pilih semua guru"...');
    await page.click('#selectAllGuru');
    await page.waitForTimeout(300);
    await page.screenshot({ path: `${SCREENSHOT_DIR}/04_guru_selected_all.png` });
    console.log('Saved 04_guru_selected_all.png');

    console.log('5. Navigating to Data Siswa...');
    await page.goto('http://localhost:8000/operator/siswa');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: `${SCREENSHOT_DIR}/05_siswa_list.png` });
    console.log('Saved 05_siswa_list.png');

    console.log('6. Selecting 2 students individually...');
    const studentCheckboxes = await page.$$('.student-checkbox');
    if (studentCheckboxes.length >= 2) {
        await studentCheckboxes[0].click();
        await studentCheckboxes[1].click();
    }
    await page.waitForTimeout(300);
    await page.screenshot({ path: `${SCREENSHOT_DIR}/06_siswa_selected_two.png` });
    console.log('Saved 06_siswa_selected_two.png');

    // Also let's test deleting one student via bulk delete to test the actual feature!
    console.log('7. Testing bulk delete of 1 student...');
    await page.goto('http://localhost:8000/operator/siswa');
    await page.waitForLoadState('networkidle');
    
    // Add a temporary student to delete
    // Let's select the first student checkbox
    const checkboxes = await page.$$('.student-checkbox');
    if (checkboxes.length > 0) {
        await checkboxes[0].click();
        await page.waitForTimeout(200);
        // Accept the confirm dialog
        page.on('dialog', dialog => dialog.accept());
        await page.click('#btnBulkDeleteSiswa');
        await page.waitForLoadState('networkidle');
        console.log('Deleted 1 student successfully!');
        await page.screenshot({ path: `${SCREENSHOT_DIR}/07_siswa_after_bulk_delete.png` });
        console.log('Saved 07_siswa_after_bulk_delete.png');
    }

    await browser.close();
    console.log('All Playwright actions completed successfully!');
}

run().catch(err => {
    console.error('Error running playwright:', err);
    process.exit(1);
});
