 async function fetchLpoData(){
    try{
        const response = await fetch("./processes/form_data_fetch/json_api.php?fetch_lpo_summary");
        const data = await response.json();
        if(data.error){
            console.error("error when fetching data" + data.error);
            return;
        }

        plotChart(data);
    } catch(error){
        console.error("Fetch error:", error);
    }
    function plotChart(lpoData){
        const canvas = document.getElementById('pieChart');
        const ctx = canvas.getContext("2d");
        const supplier = lpoData.map(item => item.name);
        const totalAmount = lpoData.map(item=> parseFloat( item.totalAmount));
        const total = totalAmount.reduce((sum, amount)=> sum + amount, 0);
          // Colors for pie sections
          const colors = ["#ff9999", "#66b3ff", "#99ff99", "#ffcc99", "#c2c2f0"];

          let startAngle =0;
          lpoData.forEach((data, index)=>{
                const sliceAngle = (parseFloat(data.approved_amount/total)*2 * Math.PI);
                ctx.beginPath();
                ctx.moveTo(200,200);
                ctx.arc(200, 200, 150, startAngle, startAngle + sliceAngle);
                ctx.closePath();
                ctx.fillStyle = colors[index % colors.length];
                ctx.fill();
                // Calculate label position
                const midAngle = startAngle + sliceAngle / 2;
                const labelX = 200 + Math.cos(midAngle) * 100;
                const labelY = 200 + Math.sin(midAngle) * 100;

                 // Draw labels
                 ctx.fillStyle = "#000";
                 ctx.font = "14px Arial";
                 ctx.fillText(data.name, labelX, labelY);
 
                 startAngle += sliceAngle;
            })

    }
}