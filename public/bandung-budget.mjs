export const defaults = {room:350000, fuel:13000, distance:360, efficiency:12, toll:180000, parking:40000, whoosh:300000, shuttle:150000, rental:650000, local:220000, food:100000, tickets:60000,games:60000};
export const independentPlans = [
    {name:'Hemat',room:250000,food:100000,tickets:30000,games:40000},
    {name:'Santai',room:350000,food:150000,tickets:40000,games:60000},
    {name:'Lebih leluasa',room:500000,food:200000,tickets:60000,games:100000}
];
export const stays = {budget:{name:'Hotel hemat',rate:250000},comfort:{name:'Hotel nyaman',rate:350000},boutique:{name:'Boutique stay',rate:550000}};
export function calculate(people, days, nights, mode, prices) {
    const cars = Math.ceil(people / (mode === 'rental' ? 6 : 4));
    const rentalDays = days + (nights === days ? 1 : 0);
    const road = ((prices.distance + (days - 2) * 60) / prices.efficiency * prices.fuel + prices.toll + prices.parking * rentalDays) * cars;
    let transport = road;
    if (mode === 'rental') transport += prices.rental * rentalDays * cars;
    if (mode === 'whoosh' || mode === 'shuttle') transport = prices[mode] * 2 * people + prices.local * rentalDays * Math.ceil(people / 4);
    const lodging = Math.ceil(people / 2) * prices.room * nights;
    const food = (days * prices.food + (nights === days ? prices.food / 3 : 0)) * people;
    const tickets = prices.tickets * (days - 1) * people;
    const games = (prices.games ?? 0) * people;
    const subtotal = transport + lodging + food + tickets + games;
    const reserve = subtotal * .1;
    const total = Math.ceil(subtotal + reserve);
    return {transport,lodging,food,tickets,games,reserve,total,perPerson:Math.ceil(total / people),cars,rooms:Math.ceil(people / 2),rentalDays};
}
