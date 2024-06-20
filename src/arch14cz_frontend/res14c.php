<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5"></script>
<style>
#loading-indicator {
	position: relative;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%); /* Center the element */
	text-align: center; /* Center text within the element */
	background-color: white;
	color: black;
}
</style>

<div class="column">
<h2 class="first">Radiocarbon Dating Resolution Calculator</h2>
<p>Calculate expected temporal resolution of radiocarbon dating based on the expected actual age of the samples.</p>
<div style="border: 1px solid grey; padding: 10px; margin: 10px;">
	<p><span style="font-weight: bold;">Enter sample age</span> (in years AD, use negative values for BC):</p>
	<label for="lowerBound">Minimum:</label>
	<input id="lowerBound" type="number" value="-18000">
	<label for="upperBound">Maximum:</label>
	<input id="upperBound" type="number" value="1950">
	<button id="plotButton">Plot</button>
	<div style="padding: 10px; margin: 10px;">
		<div id="loading-indicator" style="display: none;">Loading...</div>
		<canvas id="plotContainer" style="display: none; background-color: white;"></canvas>
	</div>
	<button id="exportButton" style="display: none;">Download Data (CSV)</button>
</div>

<!-- Explanation Text Start -->
<h3>Example Use:</h3>
<p>Let's say we want to know the expected dating resolution for samples with an actual age around 2000 BC. Here's how we can do it:</p>
<ol>
    <li><strong>Enter Age Range:</strong> In the "Enter sample age" section, input -2100 as the minimum value and -1900 as the maximum value. These correspond to 2100 BC and 1900 BC.</li>
    <li><strong>Plot the Data:</strong> Click the "Plot" button.</li>
</ol>
<h3>Understanding the Results:</h3>
<ul>
    <li>The resulting graph will show the resolution of radiocarbon dating for this age range.</li>
    <li>The grey shaded area represents the 90% confidence interval, indicating the range within which we can expect most of our dates to fall.</li>
    <li>The black line represents the mean resolution.</li>
</ul>
<h3>Interpreting the Data:</h3>
<p>For the given age range (2100-1900 BC), the graph shows that the dating resolutions vary between approximately 130 and 230 years.</p>
<p>This means that if we were to date 20 samples from this period, we can expect 19 of them to have a dating uncertainty range (2-sigma or 95.45% confidence interval) between 130 and 230 years.</p>
<p>In simpler terms, when we date samples from 2000 BC, the results will generally be accurate within a range of 130 to 230 years. This helps us understand how precise our dating is for this specific period.</p>
<!-- Explanation Text End -->

<p>Project home: <a href="https://github.com/demjanp/res14c_online">https://github.com/demjanp/res14c_online</a> (Python script used to generate the data as well as the full open source code).</p>
<p>For an overview of the method see: Svetlik et al. (2019) <a href="https://doi.org/10.1017/RDC.2019.134">DOI: 10.1017/RDC.2019.134</a>.</p>
<p>The expected measurement uncertainty for 0 BP is ±15 radiocarbon years. This value grows exponentially depending on the age of the sample (see <a href="https://doi.org/10.1017/RDC.2019.134">Svetlik et al. 2019</a> for the exact formula).</p>
<p>Radiocarbon dates are calibrated using atmospheric data from the IntCal20 dataset by: Reimer et al. (2016) <a href="https://doi.org/10.2458/azu_js_rc.55.16947">DOI: 10.2458/azu_js_rc.55.16947</a></p>
<p>Development of the Radiocarbon Dating Resolution Calculator software was supported by project OP JAC "Ready for the future: understanding long-term resilience of the human culture (RES-HUM)", Reg. No. CZ.02.01.01/00/22_008/0004593 of the MEYS CR and EU.</p>
<p><img style="margin: 10px; padding: 10px; background-color: white;" src="static/EU_MEYS.jpg"/></p>
<script>
	const lowerBoundInput = document.getElementById("lowerBound");
	const upperBoundInput = document.getElementById("upperBound");
	const plotButton = document.getElementById("plotButton");
	const exportButton = document.getElementById("exportButton");
	const plotContainer = document.getElementById("plotContainer");
	const loadingIndicator = document.getElementById("loading-indicator");

	let chart = 0;
	let filteredData = 0
	let csvBlob; // Declare the blob variable outside the function
	
	plotButton.addEventListener("click", async () => {
		loadingIndicator.style.display = 'block';  // Show loading indicator before fetching data
		plotContainer.style.display = 'none';
		
		const lowerBound = parseFloat(lowerBoundInput.value);
		const upperBound = parseFloat(upperBoundInput.value);

		const response = await fetch("static/c14_resolution_data.csv");
		const csvData = await response.text();

		const rows = csvData.split("\n").map(row => row.split(",")); // Parse CSV data

		// Filter data and extract confidence intervals
		filteredData = rows.filter(row => {
			const x = parseFloat(row[0]);
			return x >= lowerBound && x <= upperBound;
		});

		const labels = filteredData.map(row => row[0]);
		const values = filteredData.map(row => row[1]);
		const lowerCI = filteredData.map(row => row[2]);  // Lower CI
		const upperCI = filteredData.map(row => row[3]);  // Upper CI

		// Create chart data with confidence interval areas and lines
		const chartData = {
			labels: labels,
			datasets: [{
				label: 'Mean',
				data: values,
				backgroundColor: 'rgba(255, 255, 255, 0.2)',
				borderColor: 'rgba(0, 0, 0, 1)',
				borderWidth: 2,
				pointRadius: 0
			}, {
				label: '',
				data: lowerCI,
				backgroundColor: 'rgba(255, 255, 255, 1)', // Set fill color for CI area
				borderColor: 'transparent', // Hide border for area
				fill: true,
				pointRadius: 0
			}, {
				label: '90% Confidence Interval',
				data: upperCI,
				backgroundColor: 'rgba(50, 50, 50, 0.5)', // Set fill color for CI area
				borderColor: 'transparent', // Hide border for area
				fill: true,
				pointRadius: 0
			}]
		};

		if (chart !== 0) {
			chart.destroy();
		}

		chart = new Chart(plotContainer, {
			type: 'line',
			data: chartData,

			options: {
				plugins: {
					title: {
						display: true,
						text: 'Expected Resolution of C-14 Dating'
					}
				},
				scales: {
					x: {
						title: {
							display: true,
							text: 'Sample Age (years AD, negative = BC)'
						}
					},
					y: {
						title: {
							display: true,
							text: 'Resolution (years)'
						}
					}
				}
			}
		});
		
		plotContainer.style.display = 'block';
		exportButton.style.display = 'block'; // Show exportButton only after plot is generated
		loadingIndicator.style.display = 'none'; // Hide loading indicator after data processing
		
		// Prepare CSV data string with headers
		const headerRow = 'Sample Age (yrs AD);Mean Resolution (yrs);5th Percentile Resolution (yrs);95th Percentile Resolution (yrs);Uncertainty Used (C-14 yrs)\n';
		const csvContent = filteredData.map(row => row.join(";")).join("\n");
		const combinedData = headerRow + csvContent;
		csvBlob = new Blob([combinedData], { type: 'text/csv;charset=utf-8' });
	});

	exportButton.addEventListener('click', () => {
		const fileName = 'c14_resolution_export.csv';
		saveAs(csvBlob, fileName);  // Use FileSaver.js to save the blob
	});
</script>
</div>


